"""
Loads the typed district-wise allotments of Master00 > "Allotment Entry for Summary" into district_allotments.
    python -I -X utf8 database/tools/load_district_allotments.py
Cells: rows 1-4 header; cols A-D identity; cols E-F derived (formulas, not stored); cols G.. typed amounts per date (stored, zeros included).
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
problems = []
cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file LIKE %s AND sheet_name = 'Allotment Entry for Summary'", ("Master00%",))
sid, total_cells = cur.fetchone()
cur.execute("SELECT id FROM financial_years WHERE code = '2026-27'")
fy = cur.fetchone()[0]
cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id = %s ORDER BY row_no", (sid,))
rows = [(r, json.loads(c) + [None] * 40) for r, c in cur.fetchall()]
cur.execute("DELETE FROM district_allotments WHERE staging_sheet_id = %s", (sid,))

hdr = dict(rows)[4]
date_cols = {i: str(v)[:10] for i, v in enumerate(hdr) if i >= 6 and v is not None and re.match(r"^\d{4}-\d\d-\d\d", str(v))}
acct = {"header": 0, "identity": 0, "derived": 0, "typed": 0, "other": 0}
recs = []
for rno, c in rows:
    if rno < 5:
        acct["header"] += sum(1 for v in c if v is not None)
        continue
    if c[0] is None:  # spare / array-formula rows: any cell here must be a derived value
        acct["other"] += sum(1 for v in c if v is not None)
        continue
    for j, v in enumerate(c):
        if v is None:
            continue
        if j <= 3:
            acct["identity"] += 1
        elif j in (4, 5):
            acct["derived"] += 1
        elif j in date_cols and isinstance(v, (int, float)) and not isinstance(v, bool):
            acct["typed"] += 1
            recs.append((c[0], c[1], c[2], c[3], date_cols[j], round(float(v), 2), rno, j))
        else:
            acct["other"] += 1
            problems.append(f"row {rno} col {j}: unexpected {str(v)[:30]!r}")
cells = sum(acct.values())
if cells != total_cells:
    problems.append(f"cell accounting {cells} != staged {total_cells} {acct}")
if acct["other"]:
    problems.append(f"{acct['other']} cells in spare rows / unexpected columns: {acct}")
cur.executemany("INSERT INTO district_allotments (financial_year_id,budget_code,scheme,model,district,entry_date,amount,staging_sheet_id,source_row,source_col,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, r[0], r[1], r[2], r[3], r[4], r[5], sid, r[6], r[7]) for r in recs])
cur.execute("SELECT COUNT(*), SUM(amount), COUNT(DISTINCT budget_code) FROM district_allotments WHERE staging_sheet_id = %s", (sid,))
n, s, codes = cur.fetchone()
print(f"rows loaded {n} (typed cells {acct['typed']}), codes {codes}, typed total {float(s):,.2f}, cell accounting {acct} vs staged {total_cells}")
expected_total = next(c[5] for r, c in rows if r == 3)  # the sheet's own 'Allotment Up to Oct' total
print(f"sheet header total of the typed column: {expected_total:,}")
if n != len(recs) or abs(float(s) - sum(r[5] for r in recs)) > 0.005:
    problems.append("read-back differs")
if abs(float(s) - float(expected_total)) > 1:
    problems.append(f"typed total {float(s):,.0f} != sheet header total {float(expected_total):,.0f}")
if problems:
    db.rollback()
    print("ROLLED BACK:", *problems, sep="\n  ")
    sys.exit(1)
db.commit()
print("COMMITTED. All checks passed.")
