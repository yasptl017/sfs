"""
2025-26 register records the treasury result as 'Pass / Wait for LC / Rejected' = P and 'Approval Month' (no treasury date / status).
This script derives the two columns the reports use, for the vouchers of the latest register (Master All) only:
    treasury_status = 'Approved'   where pass_status = 'P'
    treasury_approval_month = approval_month
Vouchers that exist only in an older export (only_in_older_export = 1) are NOT derived: the newer register and its reports do not contain them.
The raw pass_status / approval_month stay untouched. Idempotent.    python -I -X utf8 database/tools/derive_treasury_2526.py
"""
import os
import re

import pymysql

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
E = {}
for line in open(os.path.join(ROOT, ".env"), encoding="utf-8"):
    m = re.match(r"^([A-Z_]+)=(.*)$", line.strip())
    if m:
        E[m.group(1)] = m.group(2).strip('"')
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4")
cur = db.cursor()
cur.execute("SELECT id FROM financial_years WHERE code='2025-26'")
fy = cur.fetchone()[0]
cur.execute("UPDATE vouchers SET treasury_status='Approved', treasury_approval_month=approval_month WHERE financial_year_id=%s AND pass_status='P' AND only_in_older_export=0", (fy,))
print("derived for", cur.rowcount, "rows (rows already derived are not counted twice)")
cur.execute("SELECT COUNT(*), SUM(payable_total_amount) FROM vouchers WHERE financial_year_id=%s AND treasury_status='Approved'", (fy,))
print("approved vouchers FY 2025-26:", cur.fetchone())
cur.execute("SELECT approval_month, COUNT(*) FROM vouchers WHERE financial_year_id=%s AND pass_status='P' AND only_in_older_export=0 AND (treasury_approval_month IS NULL OR treasury_approval_month<>approval_month) GROUP BY 1", (fy,))
print("rows where month differs:", cur.fetchall())
db.commit()
