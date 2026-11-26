"""
Step 5c loader: allotment ledgers (to range / from circle / adjustments), L.C. entries, cash account (credit data),
Master00 office-config DropDown. Source = staging_rows.   python -I -X utf8 database/tools/load_5c.py
All-or-nothing. Reconciles the DATABASE against the spreadsheet's own Budget Allotment figures before committing.
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
problems, info = [], []
M25 = "Master Data SF Sabarkantha 25-26 DFS.xlsx"
M26 = "Master Data SF Sabarkantha 26-27 DFS.xlsx"
H00 = "History00 SF Sabarkantha 26-27 DFS.xlsx"
M00 = "Master00 SF Sabarkantha 26-27 DFS.xlsx"
DIVW = "Division SFS 26-27 RFS.xlsx"


def fail(m):
    problems.append(m)
    if len(problems) <= 30:
        print("  !! " + m)


def txt(v):
    if v is None:
        return None
    if isinstance(v, float) and v == int(v):
        return str(int(v))
    return str(v)


def num(v, w):
    if v is None:
        return None
    if isinstance(v, (int, float)) and not isinstance(v, bool):
        return round(float(v), 6)
    fail(f"{w}: not numeric {v!r}")
    return None


def integer(v, w):
    if v is None:
        return None
    if isinstance(v, (int, float)) and not isinstance(v, bool) and v == int(v):
        return int(v)
    fail(f"{w}: not integer {v!r}")
    return None


def dt(v, w, date_only=False):
    if v is None:
        return None
    if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
        return v[:10] if date_only else (v if len(v) > 10 else v + " 00:00:00")
    fail(f"{w}: not a date {v!r}")
    return None


def canon(v):
    if v is None:
        return None
    if isinstance(v, (Decimal, float)):
        return round(float(v), 6)
    if hasattr(v, "strftime"):
        return v.strftime("%Y-%m-%d %H:%M:%S") if hasattr(v, "hour") else v.strftime("%Y-%m-%d")
    return v


def sheet(src, name):
    cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, name))
    sid, cells = cur.fetchone()
    cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
    return sid, cells, [(r, json.loads(c)) for r, c in cur.fetchall()]


def pad(c, n=60):
    return c + [None] * (n - len(c))


cur.execute("SELECT code,id FROM financial_years")
FY = dict(cur.fetchall())
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {c: i for i, c in cur.fetchall()}
DIV = OFFICE["DIV"]
RANGE = {"division": DIV, **{c.lower(): i for c, i in OFFICE.items() if c != "DIV"}}
cur.execute("SELECT budget_code, id, scheme, object_class, item, model FROM budget_codes WHERE financial_year_id=%s", (FY["2026-27"],))
BC = {r[0]: r[1:] for r in cur.fetchall()}
report = []


def check(name, a, b, tol=0.005):
    ok = abs(float(a) - float(b)) <= tol
    report.append((name, a, b, "OK" if ok else "FAIL"))
    if not ok:
        fail(f"reconciliation {name}: {a} vs {b}")


# ============================================================================== 1. raw allotment ledger
sid, total_cells, rows = sheet(H00, "Raw Data of Allotment")
for t in ("allotments_to_range", "allotments_from_circle", "allotment_adjustments"):
    cur.execute(f"DELETE FROM {t} WHERE staging_sheet_id=%s", (sid,))
fy = FY["2026-27"]
acct = collections.Counter()
A, B, C = [], [], []
derived = {"A": collections.defaultdict(set), "B": collections.defaultdict(set)}
mism = collections.Counter()
for rno, c in rows:
    c = pad(c, 50)
    nonempty = [j for j, v in enumerate(c) if v is not None]
    if rno < 4:
        acct["header"] += len(nonempty)
        continue
    for j in nonempty:
        if j <= 8 or 17 <= j <= 25 or 36 <= j <= 43:
            acct["mapped"] += 1
        elif 9 <= j <= 14 or 26 <= j <= 35:
            acct["derived"] += 1
        else:
            acct["other"] += 1
            fail(f"ledger row {rno} col {j}: unexpected cell {str(c[j])[:30]!r}")
    w = f"ledger row {rno}"
    if c[1] is not None or c[4] is not None:  # table A: allotment to range
        code, rng = c[1], str(c[2]).strip().lower() if c[2] is not None else None
        if code not in BC or rng not in RANGE or c[4] is None:
            fail(f"{w}: A bad code/range/amount {code!r} {c[2]!r} {c[4]!r}")
            continue
        A.append((rno, BC[code][0], RANGE[rng], code, integer(c[0], w), num(c[3], w), num(c[4], w), txt(c[5]), dt(c[6], w), txt(c[7]), integer(c[8], w)))
        for k, j in (("scheme", 9), ("class", 10), ("item", 11), ("model", 12), ("payment", 13)):
            if c[j] is not None:
                derived["A"][(code, k)].add(c[j])
    elif any(c[j] is not None for j in range(0, 9)):
        fail(f"{w}: partial table A cells without code/amount")
    if c[18] is not None or c[21] is not None:  # table B: allotment from circle
        code = c[18]
        if code not in BC or c[21] is None:
            fail(f"{w}: B bad code/amount {code!r} {c[21]!r}")
            continue
        B.append((rno, BC[code][0], code, integer(c[17], w), num(c[19], w), num(c[20], w), num(c[21], w), dt(c[22], w), dt(c[23], w), txt(c[24]), integer(c[25], w)))
        for k, j in (("scheme", 26), ("class", 27), ("item", 28), ("model", 29), ("payment", 30), ("wing", 31), ("major", 32), ("demand", 33)):
            if c[j] is not None:
                derived["B"][(code, k)].add(txt(c[j]))
    if c[37] is not None or c[38] is not None or c[39] is not None:  # table C: adjustments
        f_, t_ = c[37], c[39]
        if f_ not in BC or t_ not in BC or c[38] is None:
            fail(f"{w}: C bad from/to/amount {f_!r} {t_!r} {c[38]!r}")
            continue
        C.append((rno, BC[f_][0], BC[t_][0], integer(c[36], w), num(c[38], w), txt(c[40]), dt(c[41], w), txt(c[42]), integer(c[43], w)))
if sum(acct.values()) != total_cells:
    fail(f"ledger cell accounting {sum(acct.values())} != {total_cells} {dict(acct)}")
print(f"ledger: A={len(A)} B={len(B)} C={len(C)} cells {dict(acct)} (staged {total_cells})")

cur.executemany("INSERT INTO allotments_to_range (financial_year_id,division_id,range_id,budget_code_id,ledger_sr,target,amount,remark,source_time_stamp,entered_by,times_edited,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, a[2], a[1], a[4], a[5], a[6], a[7], a[8], a[9], a[10], sid, a[0]) for a in A])
cur.executemany("INSERT INTO allotments_from_circle (financial_year_id,division_id,budget_code_id,ledger_sr,rate,target,amount,allotment_date,source_time_stamp,entered_by,times_edited,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, b[1], b[3], b[4], b[5], b[6], b[7], b[8], b[9], b[10], sid, b[0]) for b in B])
cur.executemany("INSERT INTO allotment_adjustments (financial_year_id,division_id,from_budget_code_id,to_budget_code_id,ledger_sr,amount,remark,source_time_stamp,entered_by,times_edited,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, x[1], x[2], x[3], x[4], x[5], x[6], x[7], x[8], sid, x[0]) for x in C])

# read-back diffs
cur.execute("SELECT source_row,range_id,budget_code_id,ledger_sr,target,amount,remark,source_time_stamp,entered_by,times_edited FROM allotments_to_range WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
ok = [tuple(canon(x) for x in r) for r in cur.fetchall()] == sorted([tuple(canon(x) for x in (a[0], a[2], a[1], a[4], a[5], a[6], a[7], a[8], a[9], a[10])) for a in A], key=lambda r: r[0])
if not ok:
    fail("allotments_to_range read-back differs")
cur.execute("SELECT source_row,budget_code_id,ledger_sr,rate,target,amount,allotment_date,source_time_stamp,entered_by,times_edited FROM allotments_from_circle WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
ok2 = [tuple(canon(x) for x in r) for r in cur.fetchall()] == sorted([tuple(canon(x) for x in (b[0], b[1], b[3], b[4], b[5], b[6], b[7], b[8], b[9], b[10])) for b in B], key=lambda r: r[0])
if not ok2:
    fail("allotments_from_circle read-back differs")
cur.execute("SELECT source_row,from_budget_code_id,to_budget_code_id,ledger_sr,amount,remark,source_time_stamp,entered_by,times_edited FROM allotment_adjustments WHERE staging_sheet_id=%s ORDER BY source_row", (sid,))
ok3 = [tuple(canon(x) for x in r) for r in cur.fetchall()] == sorted([tuple(canon(x) for x in x_) for x_ in C], key=lambda r: r[0])
if not ok3:
    fail("allotment_adjustments read-back differs")
print(f"  read-back identical: to_range={ok} from_circle={ok2} adjustments={ok3}")

# derived columns: verify against budget_codes, and complete budget_codes payment_mode/wing/major_head/demand_no
for (code, k), vals in derived["A"].items():
    pass
for tbl in ("A", "B"):
    for (code, k), vals in derived[tbl].items():
        if len(vals) > 1:
            fail(f"derived {k} for {code} inconsistent in ledger {tbl}: {vals}")
        if k in ("scheme", "class", "item", "model"):
            idx = {"scheme": 1, "class": 2, "item": 3, "model": 4}[k]
            if BC[code][idx] != txt(next(iter(vals))):
                mism[(tbl, k)] += 1
pm = collections.defaultdict(set)
for tbl in ("A", "B"):
    for (code, k), vals in derived[tbl].items():
        if k in ("payment", "wing", "major", "demand"):
            pm[(code, k)] |= {txt(v) for v in vals}
for (code, k), vals in pm.items():
    if len(vals) > 1:
        fail(f"payment/wing/major/demand for {code}/{k} conflicting: {vals}")
colmap = {"payment": "payment_mode", "wing": "wing", "major": "major_head", "demand": "demand_no"}
for (code, k), vals in pm.items():
    cur.execute(f"UPDATE budget_codes SET {colmap[k]}=%s WHERE financial_year_id=%s AND budget_code=%s", (next(iter(vals)), fy, code))
info.append(f"ledger derived Scheme/Class/Item/Model disagreeing with budget_codes: {dict(mism) or 'none'}")
info.append(f"budget_codes enriched from ledger (payment_mode etc.): {len({c for c, _ in pm})} codes")

# --- reconcile against the spreadsheet's own Budget Allotment sheet (26-27), per code
bsid, _, brows = sheet(M26, "Budget Allotment")
hdr = pad(brows[1][1], 100)
assert hdr[11] == "Allotment from Circle" and hdr[12] == "Adjusted Allotment", hdr[9:14]
row1 = pad(brows[0][1], 100)
blocks = {row1[i]: i for i in range(15, 100) if isinstance(row1[i], str) and row1[i] not in ("Phys. Target", "Allotment")}
sheet_rows = [(r, pad(c, 100)) for r, c in brows if r >= 4 and c and c[0] is not None]
cur.execute("""SELECT b.budget_code, COALESCE((SELECT f.amount FROM allotments_from_circle f WHERE f.budget_code_id=b.id ORDER BY f.source_time_stamp DESC, f.id DESC LIMIT 1),0),
               COALESCE((SELECT SUM(a.amount) FROM allotment_adjustments a WHERE a.to_budget_code_id=b.id),0) - COALESCE((SELECT SUM(a.amount) FROM allotment_adjustments a WHERE a.from_budget_code_id=b.id),0)
               FROM budget_codes b WHERE b.financial_year_id=%s""", (fy,))
dbcirc = {r[0]: (float(r[1]), float(r[2])) for r in cur.fetchall()}
cur.execute("SELECT b.budget_code, u.name, SUM(t.amount) FROM allotments_to_range t JOIN budget_codes b ON b.id=t.budget_code_id JOIN users u ON u.id=t.range_id GROUP BY b.budget_code, u.name")
dbrange = {(r[0], r[1]): float(r[2]) for r in cur.fetchall()}
n_c = n_a = n_r = 0
tot_sheet_c = tot_db_c = tot_sheet_a = tot_db_a = tot_sheet_r = tot_db_r = 0
for r, c in sheet_rows:
    code = c[0]
    sc, sa = float(c[11] or 0), float(c[12] or 0)
    dc, dadj = dbcirc.get(code, (0.0, 0.0))
    n_c += abs(dc - sc) < 0.5
    n_a += abs(dc + dadj - sa) < 0.5
    tot_sheet_c += sc; tot_db_c += dc; tot_sheet_a += sa; tot_db_a += dc + dadj
    for rng, col in blocks.items():
        nm = "SF Sabarkantha Division" if rng == "Division" else rng
        sr_, dr_ = float(c[col + 1] or 0), dbrange.get((code, nm), 0.0)
        n_r += abs(sr_ - dr_) < 0.5
        tot_sheet_r += sr_; tot_db_r += dr_
nsheet = len(sheet_rows)
check("budget codes: current circle allotment == sheet (codes matching)", n_c, nsheet, 0)
check("budget codes: adjusted allotment == sheet (codes matching)", n_a, nsheet, 0)
check("code x range cells: allotment == sheet (cells matching)", n_r, nsheet * len(blocks), 0)
check("TOTAL allotment from circle (latest per code)", tot_db_c, tot_sheet_c)
check("TOTAL adjusted allotment", tot_db_a, tot_sheet_a)
check("TOTAL allotment to ranges (sum)", tot_db_r, tot_sheet_r)
check("raw ledger sum to-range = 'Range Allotment History' Grand Total", sum(a[6] for a in A), 460220577)
info.append(f"circle raw entries sum (all restatements, NOT a total): {sum(b[6] for b in B):,.0f}; adjustments sum {sum(x[4] for x in C):,.0f}")

# ============================================================================== 2. L.C. entries
for src, code in ((M25, "2025-26"), (M26, "2026-27")):
    lsid, lcells, lrows = sheet(src, "L.C. Entry")
    cur.execute("DELETE FROM lc_entries WHERE staging_sheet_id=%s", (lsid,))
    la = collections.Counter(header=sum(1 for v in lrows[0][1] if v is not None))
    recs = []
    for rno, c in lrows[1:]:
        c = pad(c, 10)
        for j, v in enumerate(c):
            if v is None:
                continue
            if 1 <= j <= 6:
                la["mapped"] += 1
            elif j == 0:
                la["counter"] += 1  # derived entry counter in col A of the first data row
            elif j == 7 and str(v).strip().lower() == "unlocked":
                la["unlocked"] += 1
            else:
                la["other"] += 1
                fail(f"LC {code} row {rno} col {j}: unexpected {v!r}")
        if c[2] is None and c[5] is None:
            continue
        w = f"LC {code} row {rno}"
        recs.append((rno, integer(c[1], w), txt(c[2]), txt(c[3]), dt(c[4], w, True), num(c[5], w), dt(c[6], w)))
    if sum(la.values()) != lcells:
        fail(f"LC {code}: cell accounting {sum(la.values())} != {lcells} {dict(la)}")
    cur.executemany("INSERT INTO lc_entries (financial_year_id,division_id,serial_number,scheme,class,entry_date,amount,source_time_stamp,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                    [(FY[code], DIV, r[1], r[2], r[3], r[4], r[5], r[6], lsid, r[0]) for r in recs])
    cur.execute("SELECT source_row,serial_number,scheme,class,entry_date,amount,source_time_stamp FROM lc_entries WHERE staging_sheet_id=%s ORDER BY source_row", (lsid,))
    okl = [tuple(canon(x) for x in r) for r in cur.fetchall()] == [tuple(canon(x) for x in r) for r in recs]
    if not okl:
        fail(f"lc_entries {code} read-back differs")
    future = sum(1 for r in recs if r[4] and r[6] and r[4] > r[6][:10] and (int(r[4][:4]) - int(r[6][:4])) >= 1)
    check(f"L.C. total {code} == sheet 'Total LC_given'", sum(r[5] for r in recs), {"2025-26": 497378059, "2026-27": 393023464}[code])
    info.append(f"L.C. {code}: {len(recs)} entries, read-back identical={okl}; {future} entries dated a year or more AFTER their own time stamp (kept as typed)")

# ============================================================================== 3. cash account (all ranges)
csid, ccells, crows = sheet(M00, "Credit data")
cur.execute("DELETE FROM cash_accounts WHERE staging_sheet_id=%s", (csid,))
ca = collections.Counter(header=sum(1 for v in crows[0][1] if v is not None))
crecs, norange = [], []
for rno, c in crows[1:]:
    c = pad(c, 20)
    ne = [j for j, v in enumerate(c) if v is not None]
    if not ne:
        continue
    for j in ne:
        if j <= 13:
            ca["mapped"] += 1
        elif isinstance(c[j], str) and c[j].strip().lower() == "unlocked":
            ca["unlocked"] += 1
        else:
            ca["other"] += 1
            fail(f"credit data row {rno} col {j}: unexpected {str(c[j])[:40]!r}")
    w = f"credit data row {rno}"
    rname = txt(c[1])
    rid = RANGE.get(rname.strip().lower()) if rname else None
    if rname and rid is None:
        fail(f"{w}: unknown range {rname!r}")
    if not rname:
        norange.append(rno)
    if all(c[j] is None for j in range(0, 14)):
        continue
    crecs.append((rno, rid, rname, integer(c[0], w), txt(c[2]), txt(c[3]), txt(c[4]), txt(c[5]), txt(c[6]), txt(c[7]), txt(c[8]),
                  dt(c[9], w, True), dt(c[10], w, True), txt(c[11]), txt(c[12]), num(c[13], w)))
if sum(ca.values()) != ccells:
    fail(f"credit data cell accounting {sum(ca.values())} != {ccells} {dict(ca)}")
cur.executemany("INSERT INTO cash_accounts (financial_year_id,division_id,range_id,range_name,serial_number,cash_account_month,designation,round_range,details_of_recovery,designation_of_recoverer,recovery_officer_name,recovery_type,recovery_date,bank_deposit_date,bank_name_branch,challan_no,amount,staging_sheet_id,source_row,created_at,updated_at) "
                "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, r[1], r[2], r[3], r[4], r[5], r[6], r[7], r[8], r[9], r[10], r[11], r[12], r[13], r[14], r[15], csid, r[0]) for r in crecs])
cur.execute("SELECT source_row,range_id,range_name,serial_number,cash_account_month,designation,round_range,details_of_recovery,designation_of_recoverer,recovery_officer_name,recovery_type,recovery_date,bank_deposit_date,bank_name_branch,challan_no,amount FROM cash_accounts WHERE staging_sheet_id=%s ORDER BY source_row", (csid,))
okc = [tuple(canon(x) for x in r) for r in cur.fetchall()] == [tuple(canon(x) for x in r) for r in crecs]
if not okc:
    fail("cash_accounts read-back differs")
check("cash accounts total == sum of source Amount column", sum(r[15] or 0 for r in crecs), sum((pad(c, 20)[13] if isinstance(pad(c, 20)[13], (int, float)) else 0) for _, c in crows[1:]))
info.append(f"cash_accounts: {len(crecs)} rows ({len([r for r in crecs if r[1] is None])} with no range: source rows {norange[:8]}), read-back identical={okc}")

# Division workbook 'Cash Account' must be a duplicate of the Division rows in Credit data
dsid, _, drows = sheet(DIVW, "Cash Account")
def nv(v):
    if v is None or (isinstance(v, str) and not v.strip()):
        return None
    if isinstance(v, (int, float)) and not isinstance(v, bool):
        return round(float(v), 4)
    s = re.sub(r" 00:00:00$", "", str(v).strip())
    return s
dsrc = [tuple(nv(x) for x in pad(c, 20)[:14]) for r, c in drows[1:] if any(v is not None for v in c)]
mine = collections.Counter(tuple(nv(x) for x in pad(c, 20)[:14]) for r, c in crows[1:] if pad(c, 20)[1] == "Division")
missing = [t for t in dsrc if mine[t] == 0]
info.append(f"Division workbook 'Cash Account': {len(dsrc)} rows, found identical in Credit data: {len(dsrc) - len(missing)}, NOT found: {len(missing)}")
if missing:
    fail(f"Division cash account rows not in Credit data: {len(missing)} e.g. {[x for x in missing[0] if x is not None][:6]}")

# ============================================================================== 4. Master00 DropDown = office config (cell-by-cell)
osid, ocells, orows = sheet(M00, "DropDown")
cur.execute("DELETE FROM lookups WHERE staging_sheet_id=%s", (osid,))
recs = [(f"office_config_col_{j}", j, rno, txt(v)) for rno, c in orows for j, v in enumerate(c) if v is not None]
cur.executemany("INSERT INTO lookups (financial_year_id,list_name,source_col,source_row,value,staging_sheet_id,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, n, j, r, v, osid) for n, j, r, v in recs])
cur.execute("SELECT COUNT(*) FROM lookups WHERE staging_sheet_id=%s", (osid,))
check("Master00 DropDown cells -> lookups", cur.fetchone()[0], ocells, 0)

# Division DropDown: prove it is covered by Master 26-27 lists + locations
xsid, _, xrows = sheet(DIVW, "DropDown")
xh = pad(xrows[0][1], 70)
cur.execute("SELECT list_name, value FROM lookups WHERE financial_year_id=%s", (fy,))
have = set(cur.fetchall())
cur.execute("SELECT r.round, r.beat, r.place FROM range_locations r JOIN users u ON u.id=r.range_id WHERE r.financial_year_id=%s AND u.office_code='DIV'", (fy,))
divloc = set(cur.fetchall())
notin = []
for rno, c in xrows[1:]:
    c = pad(c, 70)
    if c[1] is not None and (txt(c[2]), txt(c[3]), txt(c[4])) not in divloc:
        notin.append((rno, "location", c[1:5]))
    for j in range(7, 70):
        if c[j] is not None and (txt(xh[j]) or f"unnamed_col_{j}", txt(c[j])) not in have:
            notin.append((rno, txt(xh[j]) or j, str(c[j])[:30]))
info.append(f"Division workbook 'DropDown': cells/locations not already present in Master 26-27 lookups: {len(notin)} {notin[:3]}")

# ============================================================================== result
print("\nreconciliation:")
for n, a, b, s in report:
    print(f"  {s:4} {n:68} db={a:>18,.2f}  sheet={b:>18,.2f}")
print()
for i in info:
    print("  - " + i)
if problems:
    db.rollback()
    print(f"\nROLLED BACK - {len(problems)} problem(s)")
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
