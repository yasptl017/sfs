"""
Step 5c for FINANCIAL YEAR 2025-26 (data of the three 25-26 sub-folders):
  allotment ledger (to range from Report00 25-26 / from circle from History00 25-26 / adjustments: none recorded),
  cash account (Master00 25-26 > Credit data), district-wise allotments (Master00 25-26 > Allotment Entry for Summary),
  deduction cheques (Master00 25-26 > Ded. Cheques), office-config DropDown (Master00 25-26).
    python -I -X utf8 database/tools/load_5c_2526.py
All-or-nothing; reconciles the DATABASE with the spreadsheet's own Budget Allotment sheet before committing. Only FY 2025-26 rows are written.
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
problems, info, report = [], [], []
DASH_DATES = []
M25 = "Master Data SF Sabarkantha 25-26 DFS.xlsx"
H00 = "Division Finance System/History00 SF Sabarkantha 25-26 DFS.xlsx"
M00 = "Division Finance System/Master00 SF Sabarkantha 25-26 DFS.xlsx"
R00 = "DIvision Range Fin. System/Report00 SF Sabarkantha 25-26 DFS.xlsx"
DIVW = "DIvision Range Fin. System/Division SFS 25-26 RFS.xlsx"


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
        v = re.sub(r"[T]", " ", v)           # one source cell is typed as 2025-11-21T00:00:00.000Z
        v = re.sub(r"\.\d+Z?$|Z$", "", v)
        return v[:10] if date_only else (v if len(v) > 10 else v + " 00:00:00")
    if isinstance(v, str) and v.strip() in ("-", "--"):
        DASH_DATES.append(w)                 # a dash typed where a date belongs: no date
        return None
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


def check(name, a, b, tol=0.005):
    ok = abs(float(a) - float(b)) <= tol
    report.append((name, a, b, "OK" if ok else "FAIL"))
    if not ok:
        fail(f"reconciliation {name}: {a} vs {b}")


cur.execute("SELECT code,id FROM financial_years")
FYS = dict(cur.fetchall())
fy = FYS["2025-26"]
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {c: i for i, c in cur.fetchall()}
DIV = OFFICE["DIV"]
RANGE = {"division": DIV, **{c.lower(): i for c, i in OFFICE.items() if c != "DIV"}}
cur.execute("SELECT budget_code, id, scheme, object_class, item, model FROM budget_codes WHERE financial_year_id=%s", (fy,))
BC = {r[0]: r[1:] for r in cur.fetchall()}
print(f"FY 2025-26 budget codes known: {len(BC)}")
LEDGER_ONLY = {"U168"}   # appears only in the allotment ledger (its Scheme/Class/Item/Model are #N/A); not in the Budget Allotment sheet; net allotment 0
for code in sorted(LEDGER_ONLY):
    if code not in BC:
        cur.execute("INSERT INTO budget_codes (financial_year_id,budget_code,description,created_at,updated_at) VALUES (%s,%s,%s,NOW(),NOW())",
                    (fy, code, "Code found only in the allotment ledger (its Scheme/Class/Item/Model show #N/A); not in the Budget Allotment sheet. Entries net to zero."))
        cur.execute("SELECT id, scheme, object_class, item, model FROM budget_codes WHERE financial_year_id=%s AND budget_code=%s", (fy, code))
        BC[code] = cur.fetchone()
        print(f"  budget code {code} created for the ledger-only code")
blank_amount = []

# ============================================================================== 1. ledger
sid_a, cells_a, rows_a = sheet(R00, "Raw Data of Allotment")   # allotment TO RANGE (and a copy of the circle table)
sid_b, cells_b, rows_b = sheet(H00, "Raw Data of Allotment")   # allotment FROM CIRCLE (the to-range table of this sheet is #REF!)
for t, s_ in (("allotments_to_range", sid_a), ("allotments_from_circle", sid_b), ("allotments_from_circle", sid_a), ("allotment_adjustments", sid_b)):
    cur.execute(f"DELETE FROM {t} WHERE staging_sheet_id=%s", (s_,))
A, B, acctA, acctB = [], [], collections.Counter(), collections.Counter()
derA, derB = collections.defaultdict(set), collections.defaultdict(set)
for rno, c in rows_a:
    c = pad(c, 40)
    ne = [j for j, v in enumerate(c) if v is not None]
    if rno < 4:
        acctA["header"] += len(ne)
        continue
    for j in ne:
        if j <= 8 or 17 <= j <= 25:
            acctA["mapped"] += 1
        elif 9 <= j <= 12 or 26 <= j <= 33:
            acctA["derived"] += 1
        else:
            acctA["other"] += 1
            fail(f"Report00 ledger row {rno} col {j}: unexpected cell {str(c[j])[:30]!r}")
    w = f"Report00 ledger row {rno}"
    if c[1] is not None or c[4] is not None:
        code, rng = c[1], str(c[2]).strip().lower() if c[2] is not None else None
        if code not in BC or rng not in RANGE:
            fail(f"{w}: A bad code/range {code!r} {c[2]!r}")
            continue
        if c[4] is None:
            blank_amount.append(rno)   # target given but no allotment amount typed: kept with amount 0.00
        A.append((rno, BC[code][0], RANGE[rng], code, integer(c[0], w), num(c[3], w), num(c[4], w) if c[4] is not None else 0.0, txt(c[5]), dt(c[6], w), txt(c[7]), integer(c[8], w)))
        for k, j in (("scheme", 9), ("class", 10), ("item", 11), ("model", 12)):
            if c[j] is not None:
                derA[(code, k)].add(c[j])
if sum(acctA.values()) != cells_a:
    fail(f"Report00 ledger cell accounting {sum(acctA.values())} != {cells_a} {dict(acctA)}")

circ_r00 = []
for rno, c in rows_a:   # the circle table copy inside Report00 (for the cross-check)
    c = pad(c, 40)
    if rno >= 4 and (c[18] is not None or c[21] is not None):
        circ_r00.append((integer(c[17], "r00"), c[18], num(c[19], "r"), num(c[20], "r"), num(c[21], "r"), dt(c[22], "r"), dt(c[23], "r"), txt(c[24]), integer(c[25], "r")))

for rno, c in rows_b:
    c = pad(c, 50)
    ne = [j for j, v in enumerate(c) if v is not None]
    if rno < 4:
        acctB["header"] += len(ne)
        continue
    for j in ne:
        if 17 <= j <= 25:
            acctB["mapped"] += 1
        elif 26 <= j <= 33:
            acctB["derived"] += 1
        elif j in (16, 34, 35, 36):
            acctB["pivot filter text"] += 1   # 'Is the date ... in Between' helper columns
        else:
            acctB["other"] += 1
            fail(f"History00 ledger row {rno} col {j}: unexpected cell {str(c[j])[:30]!r}")
    w = f"History00 ledger row {rno}"
    if c[18] is not None or c[21] is not None:
        code = c[18]
        if code not in BC or c[21] is None:
            fail(f"{w}: B bad code/amount {code!r} {c[21]!r}")
            continue
        B.append((rno, BC[code][0], code, integer(c[17], w), num(c[19], w), num(c[20], w), num(c[21], w), dt(c[22], w), dt(c[23], w), txt(c[24]), integer(c[25], w)))
        for k, j in (("scheme", 26), ("class", 27), ("item", 28), ("model", 29), ("payment", 30), ("wing", 31), ("major", 32), ("demand", 33)):
            if c[j] is not None:
                derB[(code, k)].add(txt(c[j]))
if sum(acctB.values()) != cells_b:
    fail(f"History00 ledger cell accounting {sum(acctB.values())} != {cells_b} {dict(acctB)}")
# History00's own to-range table must be empty (#REF!) so that nothing is lost by taking it from Report00
h_to_range = sum(1 for rno, c in rows_b if rno >= 4 and any(pad(c, 50)[j] is not None for j in range(1, 9)))
print(f"ledger: to-range rows (Report00) A={len(A)}; circle rows (History00) B={len(B)}; History00 to-range cells present: {h_to_range}; cells Report00 {dict(acctA)}; History00 {dict(acctB)}")
if h_to_range:
    fail("History00 has to-range rows that Report00 lacks")
# the circle table of Report00 must equal History00's
b_key = collections.Counter((b[3], b[2], b[4], b[5], b[6], b[7], b[8], b[9], b[10]) for b in B)
r_key = collections.Counter(circ_r00)
info.append(f"circle table: History00 {sum(b_key.values())} rows, Report00 copy {sum(r_key.values())} rows, identical multiset: {b_key == r_key}; only in History00: {sum((b_key - r_key).values())}, only in Report00: {sum((r_key - b_key).values())}")
if (r_key - b_key):
    fail("Report00 circle table has rows that History00 lacks")

cur.executemany("INSERT INTO allotments_to_range (financial_year_id,division_id,range_id,budget_code_id,ledger_sr,target,amount,remark,source_time_stamp,entered_by,times_edited,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, a[2], a[1], a[4], a[5], a[6], a[7], a[8], a[9], a[10], sid_a, a[0]) for a in A])
cur.executemany("INSERT INTO allotments_from_circle (financial_year_id,division_id,budget_code_id,ledger_sr,rate,target,amount,allotment_date,source_time_stamp,entered_by,times_edited,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, DIV, b[1], b[3], b[4], b[5], b[6], b[7], b[8], b[9], b[10], sid_b, b[0]) for b in B])
cur.execute("SELECT source_row,range_id,budget_code_id,ledger_sr,target,amount,remark,source_time_stamp,entered_by,times_edited FROM allotments_to_range WHERE staging_sheet_id=%s ORDER BY source_row", (sid_a,))
ok1 = [tuple(canon(x) for x in r) for r in cur.fetchall()] == sorted([tuple(canon(x) for x in (a[0], a[2], a[1], a[4], a[5], a[6], a[7], a[8], a[9], a[10])) for a in A], key=lambda r: r[0])
cur.execute("SELECT source_row,budget_code_id,ledger_sr,rate,target,amount,allotment_date,source_time_stamp,entered_by,times_edited FROM allotments_from_circle WHERE staging_sheet_id=%s ORDER BY source_row", (sid_b,))
ok2 = [tuple(canon(x) for x in r) for r in cur.fetchall()] == sorted([tuple(canon(x) for x in (b[0], b[1], b[3], b[4], b[5], b[6], b[7], b[8], b[9], b[10])) for b in B], key=lambda r: r[0])
if not ok2:
    cur.execute("SELECT source_row,budget_code_id,ledger_sr,rate,target,amount,allotment_date,source_time_stamp,entered_by,times_edited FROM allotments_from_circle WHERE staging_sheet_id=%s ORDER BY source_row", (sid_b,))
    got = [tuple(canon(x) for x in r) for r in cur.fetchall()]
    exp = sorted([tuple(canon(x) for x in (b[0], b[1], b[3], b[4], b[5], b[6], b[7], b[8], b[9], b[10])) for b in B], key=lambda r: r[0])
    print("   first differing circle rows:", [(g, e) for g, e in zip(got, exp) if g != e][:2], len(got), len(exp))
if not (ok1 and ok2):
    fail("allotment read-back differs")
print(f"  read-back identical: to_range={ok1} from_circle={ok2}")

info.append(f"to-range ledger rows with a target but no typed allotment amount (stored as 0.00): {len(blank_amount)} rows {blank_amount}")
# derived columns vs budget_codes; complete payment_mode/wing/major/demand (only where still empty)
mism = collections.Counter()
for (code, k), vals in list(derA.items()) + list(derB.items()):
    if len(vals) > 1:
        fail(f"derived {k} for {code} inconsistent: {vals}")
    if k in ("scheme", "class", "item", "model") and code not in LEDGER_ONLY:
        idx = {"scheme": 1, "class": 2, "item": 3, "model": 4}[k]
        if BC[code][idx] != txt(next(iter(vals))):
            mism[k] += 1
pm = collections.defaultdict(set)
for (code, k), vals in derB.items():
    if k in ("payment", "wing", "major", "demand"):
        pm[(code, k)] |= {txt(v) for v in vals}
colmap = {"payment": "payment_mode", "wing": "wing", "major": "major_head", "demand": "demand_no"}
for (code, k), vals in pm.items():
    if len(vals) > 1:
        fail(f"{k} for {code} conflicting: {vals}")
    cur.execute(f"UPDATE budget_codes SET {colmap[k]}=%s WHERE financial_year_id=%s AND budget_code=%s", (next(iter(vals)), fy, code))
info.append(f"ledger derived Scheme/Class/Item/Model disagreeing with budget_codes: {dict(mism) or 'none'}; budget_codes enriched: {len({c for c, _ in pm})} codes")

# reconcile with the sheet's own Budget Allotment (Master Data 25-26)
NET_ADJ = []
bsid, _, brows = sheet(M25, "Budget Allotment")
hdr = pad(brows[1][1], 100)
assert hdr[11] == "Allotment from Circle" and hdr[12] == "Adjusted Allotment", hdr[9:14]
row1 = pad(brows[0][1], 100)
blocks = {row1[i]: i for i in range(15, 100) if isinstance(row1[i], str) and row1[i] not in ("Phys. Target", "Allotment")}
sheet_rows = [(r, pad(c, 100)) for r, c in brows if r >= 4 and c and c[0] is not None]
cur.execute("""SELECT b.budget_code, COALESCE((SELECT f.amount FROM allotments_from_circle f WHERE f.budget_code_id=b.id ORDER BY f.source_time_stamp DESC, f.id DESC LIMIT 1),0),
               COALESCE((SELECT SUM(a.amount) FROM allotment_adjustments a WHERE a.to_budget_code_id=b.id),0) - COALESCE((SELECT SUM(a.amount) FROM allotment_adjustments a WHERE a.from_budget_code_id=b.id),0)
               FROM budget_codes b WHERE b.financial_year_id=%s""", (fy,))
dbcirc = {r[0]: (float(r[1]), float(r[2])) for r in cur.fetchall()}
cur.execute("SELECT b.budget_code, u.name, SUM(t.amount) FROM allotments_to_range t JOIN budget_codes b ON b.id=t.budget_code_id JOIN users u ON u.id=t.range_id WHERE t.financial_year_id=%s GROUP BY b.budget_code, u.name", (fy,))
dbrange = {(r[0], r[1]): float(r[2]) for r in cur.fetchall()}
# net adjustment per code = sheet 'Adjusted Allotment' - circle (the transfer ledger itself is #REF! in the source)
for r, c in sheet_rows:
    code = c[0]
    net = round(float(c[12] or 0) - dbcirc.get(code, (0.0, 0.0))[0], 2)
    if abs(net) >= 0.005:
        NET_ADJ.append((BC[code][0], net, r))
for bid, net, r in NET_ADJ:
    cur.execute("INSERT INTO allotment_net_adjustments (financial_year_id,division_id,budget_code_id,amount,remark,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                (fy, DIV, bid, net, "Adjusted Allotment minus Circle Allotment from the sheet Budget Allotment; the transfer ledger is #REF! in the 2025-26 workbooks", bsid, r))
info.append(f"net allotment adjustments recovered from the sheet: {len(NET_ADJ)} codes, sum {sum(n for _, n, _ in NET_ADJ):,.2f} (moved between codes), positive part {sum(n for _, n, _ in NET_ADJ if n > 0):,.0f}")
n_c = n_a = n_r = 0
ts_c = td_c = ts_a = td_a = ts_r = td_r = 0
for r, c in sheet_rows:
    code = c[0]
    sc, sa = float(c[11] or 0), float(c[12] or 0)
    dc, dadj = dbcirc.get(code, (0.0, 0.0))
    n_c += abs(dc - sc) < 0.5
    netadj = sum(n for bid, n, rr in NET_ADJ if bid == BC[code][0])
    n_a += abs(dc + dadj + netadj - sa) < 0.5
    ts_c += sc; td_c += dc; ts_a += sa; td_a += dc + dadj + netadj
    for rng, col in blocks.items():
        nm = "SF Sabarkantha Division" if rng == "Division" else rng
        sr_, dr_ = float(c[col + 1] or 0), dbrange.get((code, nm), 0.0)
        n_r += abs(sr_ - dr_) < 0.5
        ts_r += sr_; td_r += dr_
ns = len(sheet_rows)
check("budget codes: current circle allotment == sheet (codes matching)", n_c, ns, 0)
check("budget codes: adjusted allotment == sheet (codes matching)", n_a, ns, 0)
check("code x range cells: allotment == sheet (cells matching)", n_r, ns * len(blocks), 0)
check("TOTAL allotment from circle (latest per code)", td_c, ts_c)
check("TOTAL adjusted allotment (net adjustments recovered from the sheet)", td_a, ts_a)
check("TOTAL allotment to ranges (sum)", td_r, ts_r)
info.append(f"circle raw entries sum (all restatements, NOT a total): {sum(b[6] for b in B):,.0f}; to-range entries {len(A)} sum {sum(a[6] for a in A):,.0f}")

# ============================================================================== 2. cash account (Credit data)
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
info.append(f"cash_accounts 2025-26: {len(crecs)} rows ({len([r for r in crecs if r[1] is None])} with no range: source rows {norange[:8]}), read-back identical={okc}")


def nv(v):
    if v is None or (isinstance(v, str) and not v.strip()):
        return None
    if isinstance(v, (int, float)) and not isinstance(v, bool):
        return round(float(v), 4)
    return re.sub(r" 00:00:00$", "", str(v).strip())


mine = collections.Counter(tuple(nv(x) for x in pad(c, 20)[:14]) for r, c in crows[1:] if any(v is not None for v in c))
for src in [DIVW] + [f"Range Finance System/{n} SFS 25-26 RFS.xlsx" for n in ("Bayad", "Bhiloda", "Dhansura", "Himatnagar", "Idar", "Khedbrahma", "Malpur", "Meghraj", "Modasa", "Prantij", "Talod", "Vadali", "Vijaynagar")]:
    _, _, drows = sheet(src, "Cash Account")
    dsrc = [tuple(nv(x) for x in pad(c, 20)[:14]) for r, c in drows[1:] if any(v is not None for v in c[:14])]
    missing = [t for t in dsrc if mine[t] == 0]
    info.append(f"'Cash Account' of {src.split('/')[-1][:18]:18}: {len(dsrc):3} rows, found in Credit data: {len(dsrc) - len(missing):3}, NOT found: {len(missing)}")

# --- rows of the Division / range workbooks' own 'Cash Account' sheets that are not in Master00 'Credit data'
extra_total = 0.0
extra_n = collections.Counter()
for src in [DIVW] + [f"Range Finance System/{n} SFS 25-26 RFS.xlsx" for n in ("Bayad", "Bhiloda", "Dhansura", "Himatnagar", "Idar", "Khedbrahma", "Malpur", "Meghraj", "Modasa", "Prantij", "Talod", "Vadali", "Vijaynagar")]:
    xsid, xcells, xrows = sheet(src, "Cash Account")
    cur.execute("DELETE FROM cash_accounts WHERE staging_sheet_id=%s", (xsid,))
    xa = collections.Counter(header=sum(1 for v in xrows[0][1] if v is not None))
    pool = collections.Counter(mine)
    xrecs = []
    for rno, c in xrows[1:]:
        c = pad(c, 20)
        ne = [j for j, v in enumerate(c) if v is not None]
        if not ne:
            continue
        for j in ne:
            if j <= 13:
                xa["mapped"] += 1
            elif isinstance(c[j], str) and c[j].strip().lower() == "unlocked":
                xa["unlocked"] += 1
            else:
                xa["other"] += 1
                fail(f"{src} Cash Account row {rno} col {j}: unexpected {str(c[j])[:30]!r}")
        key = tuple(nv(x) for x in c[:14])
        if not any(v is not None for v in c[:14]):
            continue
        if pool[key] > 0:
            pool[key] -= 1          # already loaded from Credit data
            continue
        w = f"{src} Cash Account row {rno}"
        rname = txt(c[1])
        rid = RANGE.get(rname.strip().lower()) if rname else None
        xrecs.append((rno, rid, rname, integer(c[0], w), txt(c[2]), txt(c[3]), txt(c[4]), txt(c[5]), txt(c[6]), txt(c[7]), txt(c[8]),
                      dt(c[9], w, True), dt(c[10], w, True), txt(c[11]), txt(c[12]), num(c[13], w)))
    if sum(xa.values()) != xcells:
        fail(f"{src} Cash Account cell accounting {sum(xa.values())} != {xcells} {dict(xa)}")
    cur.executemany("INSERT INTO cash_accounts (financial_year_id,division_id,range_id,range_name,serial_number,cash_account_month,designation,round_range,details_of_recovery,designation_of_recoverer,recovery_officer_name,recovery_type,recovery_date,bank_deposit_date,bank_name_branch,challan_no,amount,staging_sheet_id,source_row,created_at,updated_at) "
                    "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                    [(fy, DIV, r[1], r[2], r[3], r[4], r[5], r[6], r[7], r[8], r[9], r[10], r[11], r[12], r[13], r[14], r[15], xsid, r[0]) for r in xrecs])
    cur.execute("SELECT source_row,range_id,range_name,serial_number,cash_account_month,designation,round_range,details_of_recovery,designation_of_recoverer,recovery_officer_name,recovery_type,recovery_date,bank_deposit_date,bank_name_branch,challan_no,amount FROM cash_accounts WHERE staging_sheet_id=%s ORDER BY source_row", (xsid,))
    if [tuple(canon(x) for x in r) for r in cur.fetchall()] != [tuple(canon(x) for x in r) for r in xrecs]:
        fail(f"cash_accounts read-back differs for {src}")
    extra_n[src.split("/")[-1][:12]] = len(xrecs)
    extra_total += sum(r[15] or 0 for r in xrecs)
info.append(f"cash account rows found only in the Division / range workbooks (loaded, source = that workbook): {sum(extra_n.values())} rows, amount {extra_total:,.2f}: {dict(extra_n)}")

# ============================================================================== 3. district-wise allotments (Allotment Entry for Summary)
dsid, dcells, drows = sheet(M00, "Allotment Entry for Summary")
cur.execute("DELETE FROM district_allotments WHERE staging_sheet_id=%s", (dsid,))
drows = [(r, c + [None] * 60) for r, c in drows]
hdrd = dict(drows)[4]
date_cols = {i: str(v)[:10] for i, v in enumerate(hdrd) if i >= 6 and v is not None and re.match(r"^\d{4}-\d\d-\d\d", str(v))}
dacct = {"header": 0, "identity": 0, "derived": 0, "typed": 0, "other": 0}
drecs = []
for rno, c in drows:
    if rno < 5:
        dacct["header"] += sum(1 for v in c if v is not None)
        continue
    if c[0] is None:
        dacct["other"] += sum(1 for v in c if v is not None)
        continue
    for j, v in enumerate(c):
        if v is None:
            continue
        if j <= 3:
            dacct["identity"] += 1
        elif j in (4, 5):
            dacct["derived"] += 1
        elif j in date_cols and isinstance(v, (int, float)) and not isinstance(v, bool):
            dacct["typed"] += 1
            drecs.append((c[0], c[1], c[2], c[3], date_cols[j], round(float(v), 2), rno, j))
        else:
            dacct["other"] += 1
            fail(f"district row {rno} col {j}: unexpected {str(v)[:30]!r}")
if sum(dacct.values()) != dcells:
    fail(f"district allotment cell accounting {sum(dacct.values())} != {dcells} {dacct}")
cur.executemany("INSERT INTO district_allotments (financial_year_id,budget_code,scheme,model,district,entry_date,amount,staging_sheet_id,source_row,source_col,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, r[0], r[1], r[2], r[3], r[4], r[5], dsid, r[6], r[7]) for r in drecs])
cur.execute("SELECT COUNT(*), COALESCE(SUM(amount),0) FROM district_allotments WHERE staging_sheet_id=%s", (dsid,))
dn, dsum = cur.fetchone()
hdr_total = next(c[5] for r, c in drows if r == 3)
check("district allotments typed total == sheet header total", float(dsum), float(hdr_total), 1)
check("district allotment rows == typed cells", dn, len(drecs), 0)
info.append(f"district_allotments 2025-26: {dn} rows, {len({r[0] for r in drecs})} budget codes, {sum(1 for r in drecs if r[3] is None)} without district")

# ============================================================================== 4. deduction cheques
ksid, kcells, krows = sheet(M00, "Ded. Cheques")
cur.execute("DELETE FROM deduction_cheques WHERE staging_sheet_id=%s", (ksid,))
ka = collections.Counter(header=sum(1 for v in krows[0][1] if v is not None))
krecs = []
for rno, c in krows[1:]:
    c = pad(c, 12)
    ne = [j for j, v in enumerate(c) if v is not None]
    if not ne:
        continue
    for j in ne:
        if j <= 8:
            ka["mapped"] += 1
        elif isinstance(c[j], str) and c[j].strip().lower() == "unlocked":
            ka["unlocked"] += 1
        else:
            ka["other"] += 1
            fail(f"Ded. Cheques row {rno} col {j}: unexpected {str(c[j])[:30]!r}")
    w = f"Ded. Cheques row {rno}"
    krecs.append((rno, txt(c[0]), integer(c[1], w), txt(c[2]), dt(c[3], w, True), txt(c[4]), txt(c[5]), num(c[6], w), dt(c[7], w), txt(c[8])))
if sum(ka.values()) != kcells:
    fail(f"Ded. Cheques cell accounting {sum(ka.values())} != {kcells} {dict(ka)}")
cur.executemany("INSERT INTO deduction_cheques (financial_year_id,advice_month,advice_no,details_of_cheque,cheque_date,cheque_no,payee,amount,source_time_stamp,entered_by,staging_sheet_id,source_row,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())",
                [(fy, r[1], r[2], r[3], r[4], r[5], r[6], r[7], r[8], r[9], ksid, r[0]) for r in krecs])
cur.execute("SELECT source_row,advice_month,advice_no,details_of_cheque,cheque_date,cheque_no,payee,amount,source_time_stamp,entered_by FROM deduction_cheques WHERE staging_sheet_id=%s ORDER BY source_row", (ksid,))
okk = [tuple(canon(x) for x in r) for r in cur.fetchall()] == [tuple(canon(x) for x in r) for r in krecs]
if not okk:
    fail("deduction_cheques read-back differs")
check("deduction cheques total == sum of source Cheque Amount column", sum(r[7] or 0 for r in krecs), sum((pad(c, 12)[6] if isinstance(pad(c, 12)[6], (int, float)) else 0) for _, c in krows[1:]))
info.append(f"deduction_cheques 2025-26: {len(krecs)} rows, read-back identical={okk}")

# ============================================================================== 5. office-config DropDown (Master00 25-26)
osid, ocells, orows = sheet(M00, "DropDown")
cur.execute("DELETE FROM lookups WHERE staging_sheet_id=%s", (osid,))
recs = [(f"office_config_col_{j}", j, rno, txt(v)) for rno, c in orows for j, v in enumerate(c) if v is not None]
cur.executemany("INSERT INTO lookups (financial_year_id,list_name,source_col,source_row,value,staging_sheet_id,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,NOW(),NOW())", [(fy, n, j, r, v, osid) for n, j, r, v in recs])
cur.execute("SELECT COUNT(*) FROM lookups WHERE staging_sheet_id=%s", (osid,))
check("Master00 25-26 DropDown cells -> lookups", cur.fetchone()[0], ocells, 0)

print("\nreconciliation:")
for n, a, b, s_ in report:
    print(f"  {s_:4} {n:70} db={a:>18,.2f}  sheet={b:>18,.2f}")
print()
for i in info:
    print("  - " + i)
if problems:
    db.rollback()
    print(f"\nROLLED BACK - {len(problems)} problem(s)")
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
