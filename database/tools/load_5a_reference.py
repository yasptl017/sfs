"""
Step 5a loader: budget_codes, range_locations, lookups  (from staging_rows, years 2025-26 and 2026-27).

    python -I -X utf8 database/tools/load_5a_reference.py

Idempotent for these three tables (deletes only rows that came from the staged sheets it loads).
Validates by (1) cell accounting, (2) reading everything back and diffing against the source.
"""
import json
import os
import re
import sys

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
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"],
                     database=E["DB_DATABASE"], charset="utf8mb4", autocommit=False)
cur = db.cursor()

YEARS = {"Master Data SF Sabarkantha 25-26 DFS.xlsx": "2025-26", "Master Data SF Sabarkantha 26-27 DFS.xlsx": "2026-27"}
problems = []


def fail(msg):
    problems.append(msg)
    print("  !! " + msg)


def txt(v):
    if v is None:
        return None
    if isinstance(v, float) and v == int(v):
        return str(int(v))
    return str(v)


def num(v, what):
    if v is None:
        return None
    if isinstance(v, bool) or not isinstance(v, (int, float)):
        fail(f"non-numeric {what}: {v!r}")
        return None
    return v


def sheet_id(src, name):
    cur.execute("SELECT id, expected_cells, expected_rows FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, name))
    r = cur.fetchone()
    assert r, (src, name)
    return r


def rows_of(sid):
    cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
    return [(r, json.loads(c)) for r, c in cur.fetchall()]


def pad(c, n):
    return c + [None] * (n - len(c))


cur.execute("SELECT code, id FROM financial_years")
FY = dict(cur.fetchall())
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {code: uid for uid, code in cur.fetchall()}
RANGE_BY_NAME = {k.lower(): v for k, v in {"Division": OFFICE["DIV"], **{c.title(): i for c, i in OFFICE.items() if c != "DIV"}}.items()}

summary = []

for src, fycode in YEARS.items():
    fy = FY[fycode]
    print(f"\n=== {fycode}  ({src})")

    # ---------------- budget codes ----------------
    bsid, bcells, brows = sheet_id(src, "Budget Allotment")
    cur.execute("DELETE FROM budget_codes WHERE staging_sheet_id=%s", (bsid,))
    expected = []
    for rno, c in rows_of(bsid):
        if rno < 3:
            continue  # row 1 = totals strip, row 2 = header
        c = pad(c, 12)
        if c[0] is None:
            continue
        code = str(c[0])
        if code != code.strip():
            fail(f"budget code has surrounding spaces at row {rno}: {code!r}")
        expected.append((rno, code.strip(), txt(c[1]), txt(c[2]), txt(c[3]), txt(c[4]), txt(c[5]), txt(c[6]), txt(c[7]), txt(c[8]),
                         num(c[9], f"rate row {rno}"), num(c[10], f"target row {rno}")))
    for e in expected:
        cur.execute(
            "INSERT INTO budget_codes (financial_year_id,staging_sheet_id,source_row,budget_code,operating_head,scheme,object_class,object_code,"
            "description,item,model,scheme_year,rate,target,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
            (fy, bsid, e[0], e[1], e[2], e[3], e[4], e[5], e[6], e[7], e[8], e[9], e[10], e[11]))
    cur.execute("SELECT source_row,budget_code,operating_head,scheme,object_class,object_code,description,item,model,scheme_year,rate,target "
                "FROM budget_codes WHERE staging_sheet_id=%s ORDER BY source_row", (bsid,))
    back = [tuple(float(x) if hasattr(x, "as_tuple") else x for x in r) for r in cur.fetchall()]
    exp_cmp = [tuple(float(x) if isinstance(x, (int, float)) and not isinstance(x, bool) and i >= 10 else x for i, x in enumerate(e)) for e in expected]
    ok = back == exp_cmp
    if not ok:
        fail(f"budget_codes read-back differs from source ({fycode})")
    print(f"  budget_codes: source rows={len(expected)} in DB={len(back)} distinct codes={len({e[1] for e in expected})} read-back identical={ok}")
    summary.append((fycode, "budget_codes", len(expected), len(back), ok))

    # ---------------- locations + lookups (DropDown) ----------------
    dsid, dcells, drows = sheet_id(src, "DropDown")
    cur.execute("DELETE FROM range_locations WHERE staging_sheet_id=%s", (dsid,))
    cur.execute("DELETE FROM lookups WHERE staging_sheet_id=%s", (dsid,))
    drs = rows_of(dsid)
    header = pad(drs[0][1], 70)
    assert drs[0][0] == 1
    hdr_cells = sum(1 for h in header if h is not None)
    accounted = hdr_cells
    loc_expected, lk_expected = [], []
    for rno, c in drs[1:]:
        c = pad(c, 70)
        rng = c[1]
        rid = None
        if rng is not None:
            rid = RANGE_BY_NAME.get(str(rng).strip().lower())
            if rid is None:
                fail(f"row {rno}: unknown range {rng!r}")
                continue
            label = str(rng) if str(rng) != str(rng).strip().title() and str(rng).strip().lower() != "division" else None
            if str(rng).strip().lower() == "division":
                label = None if str(rng) == "Division" else str(rng)
            ha = num(c[5], f"Ha. row {rno}")
            sr = c[0]
            if sr is not None and (not isinstance(sr, (int, float)) or sr != int(sr)):
                fail(f"row {rno}: Sr not integer: {sr!r}")
                sr = None
            loc_expected.append((rno, rid, int(sr) if sr is not None else None, txt(c[2]), txt(c[3]), txt(c[4]), ha, txt(c[6]), label))
            accounted += sum(1 for k in range(0, 7) if c[k] is not None)
            first_list_col = 7
        else:
            first_list_col = 1  # no range: Sr (col 0) is just a counter; everything else goes to lookups
            if c[0] is not None:
                accounted += 1  # counter cell, deliberately not stored
        for j in range(first_list_col, len(c)):
            if c[j] is not None:
                name = txt(header[j]) if header[j] is not None else f"unnamed_col_{j}"
                lk_expected.append((j, rno, name, txt(c[j])))
                accounted += 1
    for e in loc_expected:
        cur.execute("INSERT INTO range_locations (financial_year_id,range_id,sr,round,beat,place,hectares,place_year,source_label,staging_sheet_id,source_row,created_at,updated_at) "
                    "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                    (fy, e[1], e[2], e[3], e[4], e[5], e[6], e[7], e[8], dsid, e[0]))
    for j, rno, name, val in lk_expected:
        cur.execute("INSERT INTO lookups (financial_year_id,list_name,source_col,source_row,value,staging_sheet_id,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                    (fy, name, j, rno, val, dsid))
    cur.execute("SELECT source_row,range_id,sr,round,beat,place,hectares,place_year,source_label FROM range_locations WHERE staging_sheet_id=%s ORDER BY source_row", (dsid,))
    back = [tuple(float(x) if hasattr(x, "as_tuple") else x for x in r) for r in cur.fetchall()]
    exp = [(e[0], e[1], e[2], e[3], e[4], e[5], float(e[6]) if e[6] is not None else None, e[7], e[8]) for e in loc_expected]
    ok_loc = back == exp
    cur.execute("SELECT source_row,source_col,list_name,value FROM lookups WHERE staging_sheet_id=%s ORDER BY source_row,source_col", (dsid,))
    back_lk = cur.fetchall()
    exp_lk = sorted([(r, j, n, v) for j, r, n, v in lk_expected])
    ok_lk = list(back_lk) == exp_lk
    cells_ok = accounted == dcells
    if not (ok_loc and ok_lk and cells_ok):
        fail(f"DropDown validation failed ({fycode}) loc={ok_loc} lookups={ok_lk} cells={cells_ok}")
    print(f"  range_locations: rows={len(loc_expected)} in DB={len(back)} read-back identical={ok_loc}")
    print(f"  lookups: cells={len(lk_expected)} in DB={len(back_lk)} read-back identical={ok_lk}")
    print(f"  DropDown cell accounting: staged non-empty cells={dcells}, accounted for={accounted} -> {'OK' if cells_ok else 'MISMATCH'}")
    summary.append((fycode, "range_locations", len(loc_expected), len(back), ok_loc))
    summary.append((fycode, "lookups", len(lk_expected), len(back_lk), ok_lk))

if problems:
    db.rollback()
    print("\nROLLED BACK - %d problem(s)" % len(problems))
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
