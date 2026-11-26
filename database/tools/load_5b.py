"""
Step 5b loader: party / beneficiary / D.Wager registers, rate lists (SOR, Tender, Estimate, Bhav Patrak, WL Tender),
party additional limits, cancelled cheques.   python -I -X utf8 database/tools/load_5b.py
Source = staging_rows. Idempotent per staged sheet. All-or-nothing: any problem rolls everything back.
Every non-empty source cell must land in exactly one bucket: header / mapped column / 'Unlocked' UI marker / note row.
"""
import json
import os
import re
import sys
from decimal import Decimal

import pymysql

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))


def env(path):
    out = {}
    for line in open(path, encoding="utf-8"):
        m = re.match(r"^([A-Z_]+)=(.*)$", line.strip())
        if m:
            out[m.group(1)] = m.group(2).strip('"')
    return out


E = env(os.path.join(ROOT, ".env"))
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4", autocommit=False)
cur = db.cursor()
SPEC = json.load(open(os.path.join(ROOT, "database", "migration", "step5b_spec.json"), encoding="utf-8"))
problems = []
report = []


NOTE_COLS = {"party_additional_limits": (7,)}  # unnamed help-text columns


def fail(m):
    problems.append(m)
    if len(problems) <= 25:
        print("  !! " + m)


cur.execute("SELECT code,id FROM financial_years")
FY = dict(cur.fetchall())
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {c: i for i, c in cur.fetchall()}
RANGE = {"division": OFFICE["DIV"]}
RANGE.update({c.lower(): i for c, i in OFFICE.items() if c != "DIV"})
DIV = OFFICE["DIV"]


def txt(v):
    if v is None:
        return None
    if isinstance(v, float) and v == int(v):
        return str(int(v))
    return str(v)


def conv(v, kind, where):
    if v is None:
        return None
    if kind in ("str", "text"):
        return txt(v)
    if kind == "int":
        if isinstance(v, (int, float)) and not isinstance(v, bool) and v == int(v):
            return int(v)
        fail(f"{where}: not an integer: {v!r}")
        return None
    if kind == "dec":
        if isinstance(v, (int, float)) and not isinstance(v, bool):
            return round(float(v), 6)
        fail(f"{where}: not numeric: {v!r}")
        return None
    if kind == "date":
        if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
            return v[:10]
        fail(f"{where}: not a date: {v!r}")
        return None
    if kind == "datetime":
        if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
            return v if len(v) > 10 else v + " 00:00:00"
        fail(f"{where}: not a datetime: {v!r}")
        return None
    raise ValueError(kind)


def canon(v):
    """normalise DB / expected values for comparison"""
    if v is None:
        return None
    if isinstance(v, Decimal):
        return round(float(v), 6)
    if isinstance(v, float):
        return round(v, 6)
    if hasattr(v, "strftime"):
        return v.strftime("%Y-%m-%d %H:%M:%S") if hasattr(v, "hour") else v.strftime("%Y-%m-%d")
    return v


def sheet(src, name):
    cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, name))
    sid, cells = cur.fetchone()
    cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
    return sid, cells, [(r, json.loads(c)) for r, c in cur.fetchall()]


def range_of(v, where):
    if v is None:
        return None, None
    key = str(v).strip().lower()
    if key == "allranges":
        return None, str(v)
    if key in RANGE:
        return RANGE[key], str(v)
    fail(f"{where}: unknown range {v!r}")
    return None, str(v)


def pad(c, n):
    return c + [None] * (n - len(c))


# --------------------------------------------------------------------------------------------- generic tables
def load_table(s):
    tn = s["table"]
    cols = s["columns"]
    mapped = {c["idx"]: c for c in cols}
    dbcols = [c["name"] for c in cols if c["kind"] != "range"]
    has_range = s["owner"] == "range"
    for fy, (src, shname) in s["sheets"].items():
        sid, total_cells, rows = sheet(src, shname)
        cur.execute(f"DELETE FROM {tn} WHERE staging_sheet_id=%s", (sid,))
        hdr = pad(rows[0][1], 100)
        for c in cols:
            if str(hdr[c["idx"]]).strip() != c["header"]:
                fail(f"{tn}/{fy}: header mismatch at col {c['idx']}: {hdr[c['idx']]!r} vs {c['header']!r}")
        acct = dict(header=sum(1 for h in hdr if h is not None), mapped=0, unlocked=0, note=0, other=0)
        expected, notes = [], []
        for rno, c in rows[1:]:
            c = pad(c, 100)
            nonempty = [v for v in c if v is not None]
            if nonempty and all(isinstance(v, str) and v.strip().lower() == "unlocked" for v in nonempty):
                acct["unlocked"] += len(nonempty)  # a row holding only the sheet's lock marker
                continue
            if not nonempty:
                continue
            rec = {"_row": rno, "range_id": None, "range_name": None}
            for j, v in enumerate(c):
                if v is None:
                    continue
                if j in mapped:
                    acct["mapped"] += 1
                elif j in NOTE_COLS.get(tn, ()):
                    acct["note"] += 1  # declared help-text column
                elif isinstance(v, str) and v.strip().lower() == "unlocked":
                    acct["unlocked"] += 1
                else:
                    acct["other"] += 1
                    fail(f"{tn}/{fy} row {rno} col {j}: unmapped value {str(v)[:40]!r}")
            for cdef in cols:
                v = c[cdef["idx"]]
                where = f"{tn}/{fy} row {rno} {cdef['name']}"
                if cdef["kind"] == "range":
                    if has_range:
                        rec["range_id"], rec["range_name"] = range_of(v, where)
                else:
                    rec[cdef["name"]] = conv(v, cdef["kind"], where)
            expected.append(rec)
        accounted = sum(acct.values())
        ok_cells = accounted == total_cells
        if not ok_cells:
            fail(f"{tn}/{fy}: cell accounting {accounted} != staged {total_cells} {acct}")
        names = ["financial_year_id"] + (["range_id", "range_name"] if has_range else []) + (["division_id"] if s["division_id"] else []) + dbcols + ["staging_sheet_id", "source_row"]
        sql = f"INSERT INTO {tn} ({','.join(names)},created_at,updated_at) VALUES ({','.join(['%s'] * len(names))},NOW(),NOW())"
        batch = []
        for r in expected:
            vals = [FY[fy]] + ([r["range_id"], r["range_name"]] if has_range else []) + ([DIV] if s["division_id"] else []) + [r[n] for n in dbcols] + [sid, r["_row"]]
            batch.append(vals)
        cur.executemany(sql, batch)
        sel = ["source_row"] + (["range_id", "range_name"] if has_range else []) + dbcols
        cur.execute(f"SELECT {','.join(sel)} FROM {tn} WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
        back = [tuple(canon(x) for x in row) for row in cur.fetchall()]
        exp = [tuple(canon(x) for x in ([r["_row"]] + ([r["range_id"], r["range_name"]] if has_range else []) + [r[n] for n in dbcols])) for r in expected]
        ok_rb = back == exp
        if not ok_rb:
            bad = next((i for i, (a, b) in enumerate(zip(back, exp)) if a != b), None)
            fail(f"{tn}/{fy}: read-back differs (rows {len(back)} vs {len(exp)}; first diff index {bad}: {back[bad] if bad is not None else ''} vs {exp[bad] if bad is not None else ''})")
        no_range = sum(1 for r in expected if has_range and r["range_id"] is None)
        report.append((fy, tn, len(rows) - 1, len(expected), acct["note"] + acct["unlocked"], len(back), "OK" if (ok_cells and ok_rb) else "FAIL", no_range))


# --------------------------------------------------------------------------------------------- SOR
def load_sor(s):
    for fy, (src, shname) in s["sheets"].items():
        sid, total_cells, rows = sheet(src, shname)
        cur.execute("DELETE FROM sor_items WHERE staging_sheet_id=%s", (sid,))
        hdr = pad(rows[0][1], 100)
        date_cols = {}
        for j in range(6, len(hdr)):
            if hdr[j] is not None:
                if isinstance(hdr[j], str) and re.match(r"^\d{4}-\d{2}-\d{2}", hdr[j]):
                    date_cols[j] = hdr[j][:10]
                else:
                    fail(f"SOR/{fy}: header col {j} is not a date: {hdr[j]!r}")
        acct = dict(header=sum(1 for h in hdr if h is not None), mapped=0, unlocked=0, note=0, other=0)
        items, notes = [], []
        for rno, c in rows[1:]:
            c = pad(c, 100)
            nonempty = [v for v in c if v is not None]
            if not nonempty:
                continue
            if all(isinstance(v, str) and v.strip().lower() == "unlocked" for v in nonempty):
                acct["unlocked"] += len(nonempty)
                continue
            if rno == 2 and c[0] is None and c[2] is None and str(c[1]) == "AllRanges" and isinstance(c[4], str) and c[4].startswith("G1 cell") and len(nonempty) == 2:
                acct["note"] += 2  # the instruction line printed under the header of every SOR sheet
                notes.append((rno, "instruction", c[4][:40]))
                continue
            rid, rname = range_of(c[1], f"SOR/{fy} row {rno}")
            rates = []
            for j, v in enumerate(c):
                if v is None:
                    continue
                if j < 6 or j in date_cols:
                    acct["mapped"] += 1
                elif isinstance(v, str) and v.strip().lower() == "unlocked":
                    acct["unlocked"] += 1
                else:
                    acct["other"] += 1
                    fail(f"SOR/{fy} row {rno} col {j}: unmapped {str(v)[:30]!r}")
                if j in date_cols:
                    if isinstance(v, (int, float)) and not isinstance(v, bool):
                        rates.append((date_cols[j], round(float(v), 6), j))
                    else:
                        fail(f"SOR/{fy} row {rno} col {j}: rate not numeric {v!r}")
            items.append(dict(row=rno, rid=rid, rname=rname, tender=txt(c[0]), divi=txt(c[2]), sr=conv(c[3], "int", f"SOR/{fy} row {rno} sr") if c[3] is not None else None,
                              particular=txt(c[4]), unit=txt(c[5]), rates=rates))
        ok_cells = sum(acct.values()) == total_cells
        if not ok_cells:
            fail(f"SOR/{fy}: cell accounting {sum(acct.values())} != {total_cells} {acct}")
        for it in items:
            cur.execute("INSERT INTO sor_items (financial_year_id,range_id,range_name,tender_code,divi_code,serial_number,particular,unit,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                        (FY[fy], it["rid"], it["rname"], it["tender"], it["divi"], it["sr"], it["particular"], it["unit"], sid, it["row"]))
            iid = cur.lastrowid
            for d, r, j in it["rates"]:
                cur.execute("INSERT INTO sor_rates (sor_item_id,effective_from,rate,source_col) VALUES (%s,%s,%s,%s)", (iid, d, r, j))
        cur.execute("SELECT i.source_row,i.range_id,i.range_name,i.tender_code,i.divi_code,i.serial_number,i.particular,i.unit FROM sor_items i WHERE i.staging_sheet_id=%s ORDER BY i.source_row", (sid,))
        back = [tuple(canon(x) for x in r) for r in cur.fetchall()]
        exp = [tuple(canon(x) for x in (i["row"], i["rid"], i["rname"], i["tender"], i["divi"], i["sr"], i["particular"], i["unit"])) for i in items]
        cur.execute("SELECT i.source_row, r.effective_from, r.rate, r.source_col FROM sor_rates r JOIN sor_items i ON i.id=r.sor_item_id WHERE i.staging_sheet_id=%s ORDER BY i.source_row, r.source_col", (sid,))
        back_r = [tuple(canon(x) for x in r) for r in cur.fetchall()]
        exp_r = [tuple(canon(x) for x in (i["row"], d, r, j)) for i in items for d, r, j in i["rates"]]
        ok = back == exp and back_r == exp_r
        if not ok:
            fail(f"SOR/{fy}: read-back differs")
        report.append((fy, "sor_items", len(rows) - 1, len(items), acct["note"] + acct["unlocked"], len(back), "OK" if (ok and ok_cells) else "FAIL", 0))
        report.append((fy, "sor_rates", "-", len(exp_r), 0, len(back_r), "OK" if ok else "FAIL", 0))
        if notes:
            print(f"  note-row cells kept only in staging (SOR/{fy}): {notes}")


# remove the dummy test rows if any survived (table was recreated empty, so this is a no-op safeguard)
for s in SPEC:
    print(f"loading {s['table']} ...")
    (load_sor if s.get("special") else load_table)(s)

print(f"\n{'year':8}{'table':26}{'sheet rows':>11}{'loaded':>8}{'note/lock cells':>16}{'in DB':>8}  result  rows-without-range")
for r in report:
    print(f"{r[0]:8}{r[1]:26}{str(r[2]):>11}{str(r[3]):>8}{str(r[4]):>16}{str(r[5]):>8}  {r[6]:6}  {r[7]}")
if problems:
    db.rollback()
    print(f"\nROLLED BACK - {len(problems)} problem(s)")
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
