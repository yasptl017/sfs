"""
Loads the cells of Master00 > "Summary Entry" and "New Summary Entry" (monthly manual entries of the Summary reports) into summary_entry_cells,
for every Master00 workbook (2025-26 in the sub-folder; 2026-27 at the top level).   python -I -X utf8 database/tools/load_summary_entries.py
One row per non-empty cell below the 4 header rows: identity columns + month blocks (Apr..Mar, 7 columns each). Every cell must be accounted for.
"""
import collections
import json
import os
import re
import sys

import pymysql

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
E = {}
for line in open(os.path.join(ROOT, ".env"), encoding="utf-8"):
    m = re.match(r"^([A-Z_]+)=(.*)$", line.strip())
    if m:
        E[m.group(1)] = m.group(2).strip('"')
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4", autocommit=False)
cur = db.cursor()
MONTHS = ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar"]
SOURCES = [("2025-26", "Division Finance System/Master00 SF Sabarkantha 25-26 DFS.xlsx"), ("2026-27", "Master00 SF Sabarkantha 26-27 DFS.xlsx")]
SHEETS = {"Summary Entry": 3, "New Summary Entry": 4}      # first column of the first month block
problems = []
cur.execute("SELECT code,id FROM financial_years")
FY = dict(cur.fetchall())


def num(v):
    return isinstance(v, (int, float)) and not isinstance(v, bool)


for code, src in SOURCES:
    for sheet, first in SHEETS.items():
        cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, sheet))
        sid, total = cur.fetchone()
        cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
        rows = [(r, json.loads(c)) for r, c in cur.fetchall()]
        cur.execute("DELETE FROM summary_entry_cells WHERE staging_sheet_id=%s", (sid,))
        rd = dict(rows)
        row1 = rd.get(1, [])
        hdr = rd.get(4, [])
        block_of = {}          # column -> (month, field)
        for i, v in enumerate(row1):
            if v in MONTHS:
                start = i - 1
                for k in range(7):
                    block_of[start + k] = (v, str(hdr[start + k]).strip() if start + k < len(hdr) and hdr[start + k] is not None else None)
        acct = collections.Counter()
        recs = []
        ident = first   # columns before the first block
        for rno, c in rows:
            ne = [j for j, v in enumerate(c) if v is not None]
            if rno <= 4:
                acct["header rows 1-4"] += len(ne)
                continue
            for j in ne:
                v = c[j]
                month, field = block_of.get(j, (None, None))
                if j < ident:
                    acct["identity"] += 1
                elif j in block_of:
                    acct["month blocks"] += 1
                else:
                    acct["other"] += 1
                recs.append((rno, j, month, field, v))
        if sum(acct.values()) != total:
            problems.append(f"{code} {sheet}: cell accounting {sum(acct.values())} != staged {total}")
        # identity per row
        ident_of = {}
        for rno, c in rows:
            if rno >= 5 and c and c[0] is not None:
                if sheet == "New Summary Entry":
                    ident_of[rno] = (str(c[0]), c[1] if len(c) > 1 else None, c[2] if len(c) > 2 else None, c[3] if len(c) > 3 else None)
                else:
                    ident_of[rno] = (str(c[0]), c[1] if len(c) > 1 else None, c[2] if len(c) > 2 else None, None)
        batch = []
        for rno, j, month, field, v in recs:
            if rno <= 4 or j < ident:
                continue   # header / identity cells are stored as the identity columns of the rows below
            bc, sch, mod, dist = ident_of.get(rno, (None, None, None, None))
            batch.append((FY[code], sheet, bc, sch, mod, dist, month, field, round(float(v), 4) if num(v) else None, None if num(v) else str(v), sid, rno, j))
        cur.executemany("INSERT INTO summary_entry_cells (financial_year_id,sheet,budget_code,scheme,model,district,month,field,value_num,value_text,staging_sheet_id,source_row,source_col,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())", batch)
        cur.execute("SELECT COUNT(*) FROM summary_entry_cells WHERE staging_sheet_id=%s", (sid,))
        n = cur.fetchone()[0]
        cur.execute("SELECT SUM(value_num) FROM summary_entry_cells WHERE staging_sheet_id=%s", (sid,))
        s_db = float(cur.fetchone()[0] or 0)
        s_src = sum(round(float(v), 4) for rno, j, month, field, v in recs if rno > 4 and j >= ident and num(v))
        ok = n == len(batch) and abs(s_db - s_src) < 0.01
        if not ok:
            problems.append(f"{code} {sheet}: read-back differs ({n} vs {len(batch)}; sum {s_db} vs {s_src})")
        print(f"{code} {sheet:18} cells {total:6}  stored {n:6} (below-header, non-identity)  numeric sum {s_db:,.2f}  accounting {dict(acct)}  {'OK' if ok else 'FAIL'}")
if problems:
    db.rollback()
    print("ROLLED BACK:", *problems, sep="\n  ")
    sys.exit(1)
db.commit()
print("COMMITTED.")
