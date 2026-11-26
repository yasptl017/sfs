"""
Adds the plantation places of the 25-26 'Generator Template' (sheet MasterData: Sr, Range, Round, Beat, Place, Ha.) that are not yet in
range_locations of FY 2025-26 (the location list came from the DropDown sheet of Master Data 25-26).
    python -I -X utf8 database/tools/load_locations_2526.py
Rows already present (same range / round / beat / place, case-insensitive) are not duplicated. All-or-nothing with read-back.
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
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4", autocommit=False)
cur = db.cursor()
SRC = "DIvision Range Fin. System/107 Sabarkantha SF 25-26 Generator Template.xlsx"
cur.execute("SELECT id FROM financial_years WHERE code='2025-26'")
fy = cur.fetchone()[0]
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {c: i for i, c in cur.fetchall()}
RANGE = {"division": OFFICE["DIV"], **{c.lower(): i for c, i in OFFICE.items() if c != "DIV"}}
cur.execute("SELECT id FROM staging_sheets WHERE source_file=%s AND sheet_name='MasterData'", (SRC,))
sid = cur.fetchone()[0]
cur.execute("DELETE FROM range_locations WHERE staging_sheet_id=%s", (sid,))
cur.execute("SELECT range_id, round, beat, place FROM range_locations WHERE financial_year_id=%s", (fy,))
have = {(r[0], (r[1] or "").strip().lower(), (r[2] or "").strip().lower(), (r[3] or "").strip().lower()) for r in cur.fetchall()}
cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
rows = [(r, json.loads(c) + [None] * 8) for r, c in cur.fetchall()]
new, problems, seen = [], [], 0
for rno, c in rows:
    if rno < 4 or c[1] is None:
        continue
    seen += 1
    rid = RANGE.get(str(c[1]).strip().lower())
    if rid is None:
        problems.append(f"row {rno}: unknown range {c[1]!r}")
        continue
    key = (rid, str(c[2] or "").strip().lower(), str(c[3] or "").strip().lower(), str(c[4] or "").strip().lower())
    if key in have:
        continue
    have.add(key)
    ha = c[5] if isinstance(c[5], (int, float)) and not isinstance(c[5], bool) else None
    if c[5] is not None and ha is None:
        problems.append(f"row {rno}: Ha. not numeric {c[5]!r}")
    new.append((rno, int(c[0]) if isinstance(c[0], (int, float)) else None, rid, str(c[2]) if c[2] is not None else None, str(c[3]) if c[3] is not None else None, str(c[4]) if c[4] is not None else None, ha))
cur.executemany("INSERT INTO range_locations (financial_year_id,sr,range_id,round,beat,place,hectares,source_label,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,'Generator Template 25-26',%s,%s,NOW(),NOW())",
                [(fy, r[1], r[2], r[3], r[4], r[5], r[6], sid, r[0]) for r in new])
cur.execute("SELECT source_row, sr, range_id, round, beat, place, hectares FROM range_locations WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
back = [(a, b, c, d, e, f, float(g) if g is not None else None) for a, b, c, d, e, f, g in cur.fetchall()]
exp = [(r[0], r[1], r[2], r[3], r[4], r[5], r[6]) for r in new]
if back != exp:
    problems.append("read-back differs")
print(f"template rows {seen}; places not yet in FY 2025-26 locations: {len(new)} (loaded)")
for r in new:
    print("  +", r)
if problems:
    db.rollback()
    print("ROLLED BACK:", *problems, sep="\n  ")
    sys.exit(1)
db.commit()
print("COMMITTED.")
