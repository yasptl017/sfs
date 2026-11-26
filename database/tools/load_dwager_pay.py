"""
Loads the D.Wager pay registers ('D.Wager Salary', 'D.Wager Arrears' sheets) of the 14 office workbooks of 2025-26 and of the
Division workbook of 2026-27 into d_wager_salaries / d_wager_arrears (spec: database/migration/dwager_pay_spec.json).
    python -I -X utf8 database/tools/load_dwager_pay.py
Every non-empty source cell lands in exactly one bucket (header / mapped column / 'Unlocked' marker); all-or-nothing with read-back.
"""
import collections
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
SPEC = json.load(open(os.path.join(ROOT, "database", "migration", "dwager_pay_spec.json"), encoding="utf-8"))
problems = []


def fail(m):
    problems.append(m)
    if len(problems) <= 25:
        print("  !! " + m)


def txt(v):
    if v is None:
        return None
    if isinstance(v, bool):
        return str(v)
    if isinstance(v, float):
        return str(int(v)) if v == int(v) else repr(v)
    return str(v)


def conv(v, kind, w):
    if v is None:
        return None
    if kind == "text":
        return txt(v)
    if kind == "int":
        if isinstance(v, (int, float)) and not isinstance(v, bool) and v == int(v):
            return int(v)
        fail(f"{w}: not an integer {v!r}")
    elif kind == "dec":
        if isinstance(v, (int, float)) and not isinstance(v, bool):
            return round(float(v), 4)
        fail(f"{w}: not numeric {v!r}")
    elif kind == "date":
        if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
            return v[:10]
        fail(f"{w}: not a date {v!r}")
    return None


def canon(v):
    if v is None:
        return None
    if isinstance(v, (Decimal, float)):
        return round(float(v), 4)
    if hasattr(v, "strftime"):
        return v.strftime("%Y-%m-%d %H:%M:%S") if hasattr(v, "hour") else v.strftime("%Y-%m-%d")
    return v


cur.execute("SELECT code,id FROM financial_years")
FY = dict(cur.fetchall())
cur.execute("SELECT id, office_code, name FROM users WHERE office_code IS NOT NULL")
USERS = {c: (i, n) for i, c, n in cur.fetchall()}
RANGE = {"division": USERS["DIV"], **{c.lower(): v for c, v in USERS.items() if c != "DIV"}}


def range_of(src):
    if "Division SFS" in src:
        return RANGE["division"]
    name = os.path.basename(src).split(" SFS ")[0].lower()
    return RANGE[name]


summary = []
for table, s in SPEC.items():
    sheet, hrow, cols = s["sheet"], s["header_row"], s["columns"]
    mapped = {c["idx"]: c for c in cols}
    names = [c["name"] for c in cols]
    for fy, files in s["sources"].items():
        for src in files:
            cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, sheet))
            sid, total_cells = cur.fetchone()
            cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
            rows = [(r, json.loads(c)) for r, c in cur.fetchall()]
            cur.execute(f"DELETE FROM {table} WHERE staging_sheet_id=%s", (sid,))
            hdr = rows[hrow][1] + [None] * 100
            for c in cols:
                if str(hdr[c["idx"]]).strip() != c["header"]:
                    fail(f"{table} {src}: header mismatch at col {c['idx']}: {hdr[c['idx']]!r} vs {c['header']!r}")
            rid, rname = range_of(src)
            acct = collections.Counter()
            recs = []
            for rno, c in rows:
                c = c + [None] * 100
                ne = [j for j, v in enumerate(c) if v is not None]
                if rno <= rows[hrow][0]:
                    acct["header"] += len(ne)
                    continue
                if not ne:
                    continue
                for j in ne:
                    if j in mapped:
                        acct["mapped"] += 1
                    elif isinstance(c[j], str) and c[j].strip().lower() == "unlocked":
                        acct["unlocked"] += 1
                    else:
                        acct["other"] += 1
                        fail(f"{table} {src} row {rno} col {j}: unexpected {str(c[j])[:30]!r}")
                if all(c[j] is None or (j not in mapped) for j in ne):
                    continue   # a row that only carries the lock marker
                rec = {"_row": rno}
                for cd in cols:
                    rec[cd["name"]] = conv(c[cd["idx"]], cd["kind"], f"{table} {os.path.basename(src)[:14]} row {rno} {cd['name']}")
                recs.append(rec)
            if sum(acct.values()) != total_cells:
                fail(f"{table} {src}: cell accounting {sum(acct.values())} != staged {total_cells} {dict(acct)}")
            ins = ["financial_year_id", "range_id", "range_name"] + names + ["staging_sheet_id", "source_row"]
            cur.executemany(f"INSERT INTO {table} ({','.join(ins)},created_at,updated_at) VALUES ({','.join(['%s'] * len(ins))},NOW(),NOW())",
                            [[FY[fy], rid, rname] + [r[n] for n in names] + [sid, r["_row"]] for r in recs])
            cur.execute(f"SELECT source_row,{','.join(names)} FROM {table} WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
            back = [tuple(canon(x) for x in row) for row in cur.fetchall()]
            exp = [tuple(canon(x) for x in ([r["_row"]] + [r[n] for n in names])) for r in recs]
            ok = back == exp
            if not ok:
                bad = next((i for i, (a, b) in enumerate(zip(back, exp)) if a != b), None)
                fail(f"{table} {src}: read-back differs (first at {bad}: {back[bad] if bad is not None else ''} vs {exp[bad] if bad is not None else ''})")
            summary.append((table, fy, os.path.basename(src)[:24], len(recs), acct["unlocked"], "OK" if ok else "FAIL"))

print(f"{'table':18}{'year':9}{'workbook':26}{'rows':>6}{'lock cells':>11}  result")
for r in summary:
    print(f"{r[0]:18}{r[1]:9}{r[2]:26}{r[3]:>6}{r[4]:>11}  {r[5]}")
for table in SPEC:
    cur.execute(f"SELECT financial_year_id, COUNT(*) FROM {table} GROUP BY financial_year_id")
    print(table, dict(cur.fetchall()))
if problems:
    db.rollback()
    print(f"\nROLLED BACK - {len(problems)} problem(s)")
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
