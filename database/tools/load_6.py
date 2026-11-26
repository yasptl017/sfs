"""
Step 6 loader: vouchers, voucher_items, voucher_versions.   python -I -X utf8 database/tools/load_6.py
 live source      : Master00 > Master All (23 Nov 2026), one row per voucher, lines in 'Item Details'
 older sources    : History00 > Itemwise (15 Aug), Division workbook > Itemwise (6 Nov): line level -> voucher_versions
 only-in-older    : vouchers present in an older export but not in Master All -> imported LIVE, flagged only_in_older_export
All-or-nothing; rolls back on any problem.
"""
import collections
import csv
import json
import os
import random
import re
import sys
from decimal import Decimal

import pymysql

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
OUT_CSV = os.path.join(ROOT, "database", "migration", "step6_money_differences.csv")


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
SPEC = json.load(open(os.path.join(ROOT, "database", "migration", "step6_spec.json"), encoding="utf-8"))
COLS = SPEC["columns"]
MA, H00, DIVW = "Master00 SF Sabarkantha 26-27 DFS.xlsx", "History00 SF Sabarkantha 26-27 DFS.xlsx", "Division SFS 26-27 RFS.xlsx"
problems = []


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


def conv(v, kind, w):
    if v is None:
        return None
    if kind in ("str", "text", "longtext"):
        return txt(v)
    if kind == "int":
        if isinstance(v, (int, float)) and not isinstance(v, bool) and v == int(v):
            return int(v)
        fail(f"{w}: not int {v!r}")
    elif kind == "dec":
        if isinstance(v, (int, float)) and not isinstance(v, bool):
            return round(float(v), 6)
        fail(f"{w}: not numeric {v!r}")
    elif kind == "date":
        if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
            return v[:10]
        fail(f"{w}: not date {v!r}")
    elif kind == "datetime":
        if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v):
            return v if len(v) > 10 else v + " 00:00:00"
        fail(f"{w}: not datetime {v!r}")
    return None


def canon(v):
    if v is None:
        return None
    if isinstance(v, (Decimal, float)):
        return round(float(v), 6)
    if hasattr(v, "strftime"):
        return v.strftime("%Y-%m-%d %H:%M:%S") if hasattr(v, "hour") else v.strftime("%Y-%m-%d")
    return v


def nv(v):  # normalisation for comparing two exports of the same field
    if v is None or (isinstance(v, str) and v.strip() in ("", "nan", "NaN")):
        return None
    if isinstance(v, bool):
        return v
    if isinstance(v, (int, float)):
        return round(float(v), 4)
    s = re.sub(r" 00:00:00$", "", str(v).strip())
    try:
        return round(float(s), 4)
    except ValueError:
        return s


def staged(src, name):
    cur.execute("SELECT id, expected_cells FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, name))
    sid, cells = cur.fetchone()
    cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
    return sid, cells, [(r, json.loads(c)) for r, c in cur.fetchall()]


def pad(c, n):
    return c + [None] * (n - len(c))


cur.execute("SELECT code,id FROM financial_years")
FY = dict(cur.fetchall())["2026-27"]
cur.execute("SELECT id, office_code FROM users WHERE office_code IS NOT NULL")
OFFICE = {c: i for i, c in cur.fetchall()}
RANGE = {"division": OFFICE["DIV"], **{c.lower(): i for c, i in OFFICE.items() if c != "DIV"}}
ENTRY_TYPES = {"Tender Entry", "Free Entry", "D.Wagers Salary", "D.Wagers Arrears", "Bene. Entry", "WL Bene. Entry"}
BYHDR = {c["header"]: c for c in COLS}
NCOL = max(c["idx"] for c in COLS) + 1
MONEY_HDR = [c["header"] for c in COLS if c["kind"] == "dec" and c["header"] not in ("Area (Ha.)", "Ha / DCP Plants", "Total No. of Plants", "GPS-N", "GPS-E", "Approved %")]
DB_NAMES = [c["name"] for c in COLS if c["kind"] != "range"]
INS_COLS = ["financial_year_id", "range_id", "range_name"] + DB_NAMES + ["only_in_older_export", "source_kind", "staging_sheet_id", "source_row"]

for t in ("voucher_versions", "voucher_items", "vouchers"):
    cur.execute(f"DELETE FROM {t}")


def voucher_record(getter, w):
    """getter(header)->raw value. Returns dict db_name->converted + range info"""
    rec = {}
    for c in COLS:
        v = getter(c["header"])
        if c["kind"] == "range":
            if v is None or str(v).strip().lower() not in RANGE:
                fail(f"{w}: bad range {v!r}")
                rec["_range_id"], rec["_range_name"] = None, txt(v)
            else:
                rec["_range_id"], rec["_range_name"] = RANGE[str(v).strip().lower()], str(v)
        else:
            rec[c["name"]] = conv(v, c["kind"], f"{w} {c['name']}")
    if rec.get("entry_type") not in ENTRY_TYPES:
        fail(f"{w}: unknown entry type {rec.get('entry_type')!r}")
    return rec


def parse_items(raw_text, w):
    items = []
    try:
        arr = json.loads(raw_text)
    except Exception as e:
        fail(f"{w}: Item Details not JSON ({e})")
        return items
    for n, line in enumerate(arr, 1):
        if not isinstance(line, list) or len(line) != 10:
            fail(f"{w}: item {n} is not a 10-field line")
            continue
        def d(v):
            return v[:10] if isinstance(v, str) and re.match(r"^\d{4}-\d{2}-\d{2}", v) else None
        def f(v):
            if v in (None, ""):
                return None
            try:
                return round(float(v), 6)
            except (TypeError, ValueError):
                return None
        items.append(dict(line_no=n, sor=txt(line[0]) or None, desc=txt(line[1]) or None, wo_no=txt(line[2]) or None, wo_date=d(line[3]), start=d(line[4]), end=d(line[5]),
                          qty=f(line[6]), unit=txt(line[7]) or None, rate=f(line[8]), amount=f(line[9]), raw=json.dumps(line, ensure_ascii=False, separators=(",", ":"))))
    return items


# =============================================================================================== 1. live vouchers (Master All)
sid_ma, cells_ma, rows_ma = staged(MA, "Master All")
hdr_ma = rows_ma[0][1]
assert [c["header"] for c in COLS if c["idx"] < len(hdr_ma)] and all(str(hdr_ma[c["idx"]]) == c["header"] for c in COLS), "Master All header changed since gen_6"
acct = collections.Counter(header=sum(1 for h in hdr_ma if h is not None))
live = []  # (row_no, rec, items)
odd_items = collections.Counter()
for rno, c in rows_ma[1:]:
    c = pad(c, NCOL)
    acct["mapped"] += sum(1 for v in c[:NCOL] if v is not None)
    if len(c) > NCOL and any(v is not None for v in c[NCOL:]):
        fail(f"Master All row {rno}: cells beyond column {NCOL}")
    w = f"MasterAll row {rno}"
    rec = voucher_record(lambda h, c=c: c[BYHDR[h]["idx"]], w)
    items = parse_items(rec["item_details_json"], w)
    for it in items:
        raw = json.loads(it["raw"])
        if raw[6] not in (None, "") and it["qty"] is None: odd_items["quantity not numeric"] += 1
        if raw[8] not in (None, "") and it["rate"] is None: odd_items["rate not numeric"] += 1
        if raw[9] not in (None, "") and it["amount"] is None: odd_items["amount not numeric"] += 1
        for k, i in (("work order date", 3), ("work start date", 4), ("work end date", 5)):
            if raw[i] not in (None, "") and not (isinstance(raw[i], str) and re.match(r"^\d{4}-\d{2}-\d{2}", raw[i])): odd_items[k + " not a date"] += 1
    live.append((rno, rec, items))
if sum(acct.values()) != cells_ma:
    fail(f"Master All cell accounting {sum(acct.values())} != {cells_ma} {dict(acct)}")
keys = [(r[1]["_range_id"], r[1]["entry_type"], r[1]["serial_number"]) for r in live]
if len(set(keys)) != len(keys):
    fail("duplicate voucher keys in Master All")
print(f"Master All: {len(live)} vouchers, {sum(len(r[2]) for r in live)} items, cell accounting {dict(acct)} vs staged {cells_ma}")
print(f"  item fields kept only in raw_line (non-numeric / non-date): {dict(odd_items) or 'none'}")

# =============================================================================================== 2. older exports (line level)
def load_lines(src):
    sid, cells, rows = staged(src, "Itemwise")
    hdr = rows[0][1]
    names = {i: str(h) for i, h in enumerate(hdr) if h is not None and i > 0}
    vouchers = collections.OrderedDict()
    for rno, c in rows[1:]:
        c = pad(c, len(hdr))
        d = {names[i]: c[i] for i in names if c[i] is not None}
        if "Range" not in d:
            fail(f"{src} row {rno}: line without range")
            continue
        try:
            k = (RANGE[str(d["Range"]).strip().lower()], d["Entry Type"], int(float(d["Sr. No."])))
        except Exception:
            fail(f"{src} row {rno}: bad key")
            continue
        vouchers.setdefault(k, []).append((rno, d))
    return sid, vouchers


sid_h, H = load_lines(H00)
sid_d, Dv = load_lines(DIVW)
LINE_HDR = ["SOR Code", "Work Description", "Work Order No.", "Work Order Date", "Work Start Date", "Work End Date", "No. of Unit", "Unit", "SOR Rate", "Amount"]
print(f"History00 Itemwise: {len(H)} vouchers / {sum(len(v) for v in H.values())} lines; Division workbook Itemwise: {len(Dv)} vouchers / {sum(len(v) for v in Dv.values())} lines")
live_keys = set(keys)

# =============================================================================================== 3. only-in-older vouchers -> live, flagged
only_older = []
for k in list(H) + [k for k in Dv if k not in H]:
    if k not in live_keys:
        src = H if k in H else Dv
        only_older.append((k, src is H, src[k]))
print(f"vouchers only in an older export (imported LIVE, flagged): {len(only_older)} -> {[(k[1], k[2], list(RANGE)[list(RANGE.values()).index(k[0])]) for k, _, _ in only_older]}")
extra = []
for k, is_h, lines in only_older:
    first = lines[0][1]
    w = f"only-older {k}"
    rec = voucher_record(lambda h, first=first: first.get(h), w)
    raw_lines = [[(d.get(h)) for h in LINE_HDR] for _, d in lines]
    rec["item_details_json"] = json.dumps([[("" if v is None else (str(v) if not isinstance(v, str) else v)) for v in ln] for ln in raw_lines], ensure_ascii=False, separators=(",", ":"))
    items = parse_items(rec["item_details_json"], w)
    extra.append((lines[0][0], rec, items, sid_h if is_h else sid_d, "history00" if is_h else "division_workbook"))

# =============================================================================================== insert vouchers + items
def vrow(rec, flag, kind, sid, rno):
    return [FY, rec["_range_id"], rec["_range_name"]] + [rec[n] for n in DB_NAMES] + [1 if flag else 0, kind, sid, rno]


sql = f"INSERT INTO vouchers ({','.join(INS_COLS)},created_at,updated_at) VALUES ({','.join(['%s'] * len(INS_COLS))},NOW(),NOW())"
cur.executemany(sql, [vrow(r[1], False, "master_all", sid_ma, r[0]) for r in live] + [vrow(e[1], True, e[4], e[3], e[0]) for e in extra])
cur.execute("SELECT id, range_id, entry_type, serial_number FROM vouchers")
VID = {(r[1], r[2], r[3]): r[0] for r in cur.fetchall()}
item_rows, expected_items = [], 0
for rno, rec, items in [(a, b, c) for a, b, c in live] + [(e[0], e[1], e[2]) for e in extra]:
    vid = VID[(rec["_range_id"], rec["entry_type"], rec["serial_number"])]
    for it in items:
        item_rows.append((vid, it["line_no"], it["sor"], it["desc"], it["wo_no"], it["wo_date"], it["start"], it["end"], it["qty"], it["unit"], it["rate"], it["amount"], it["raw"]))
cur.executemany("INSERT INTO voucher_items (voucher_id,line_no,sor_code,description,work_order_no,work_order_date,work_start_date,work_end_date,quantity,unit,rate,amount,raw_line) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)", item_rows)

# =============================================================================================== 4. versions + differences
def snapshot(lines):
    return json.dumps({"rows": [{"r": r, "c": d} for r, d in lines]}, ensure_ascii=False, separators=(",", ":"), default=str)


live_by_key = {(r[1]["_range_id"], r[1]["entry_type"], r[1]["serial_number"]): r for r in live}
extra_keys = {(e[1]["_range_id"], e[1]["entry_type"], e[1]["serial_number"]) for e in extra}
ver_rows, diff_count, money_rows = [], collections.Counter(), []
CMP_HDR = [h for h in BYHDR if h not in ("Range", "Item Details", "Sr. No.", "Entry Type")]
for kind, srcfile, sid, VS in (("history00_snapshot", H00, sid_h, H), ("division_workbook_snapshot", DIVW, sid_d, Dv)):
    for k, lines in VS.items():
        first = lines[0][1]
        diffs = []
        if k in live_by_key and k not in extra_keys:
            live_rec = live_by_key[k][1]
            for h in CMP_HDR:
                if h not in first and live_rec[BYHDR[h]["name"]] is None:
                    continue
                if nv(first.get(h)) != nv(live_rec[BYHDR[h]["name"]]):
                    diffs.append(h)
                    if h in MONEY_HDR:
                        money_rows.append((live_rec["_range_name"], k[1], k[2], h, first.get(h), live_rec[BYHDR[h]["name"]], kind))
            old_items = [tuple(nv(d.get(h)) for h in LINE_HDR) for _, d in lines]
            new_items = [tuple(nv(x) for x in json.loads(it["raw"])) for it in live_by_key[k][2]]
            if old_items != new_items:
                diffs.append("Item Details")
        for h in diffs:
            diff_count[(kind, h)] += 1
        ver_rows.append((FY, k[0], k[1], k[2], VID[k], kind, srcfile, sid, json.dumps([r for r, _ in lines]), snapshot(lines), 1 if diffs else 0, json.dumps(diffs, ensure_ascii=False) if diffs else None))
cur.executemany("INSERT INTO voucher_versions (financial_year_id,range_id,entry_type,serial_number,voucher_id,version_kind,source_file,staging_sheet_id,source_rows,snapshot,differs_from_live,diff_fields,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())", ver_rows)

with open(OUT_CSV, "w", newline="", encoding="utf-8-sig") as fh:
    w = csv.writer(fh)
    w.writerow(["range", "entry_type", "sr_no", "field", "older_value", "live_value (Master All)", "older_source"])
    for r in sorted(money_rows, key=lambda x: (x[0], x[1], x[2], x[3], x[6])):
        w.writerow(r)

# =============================================================================================== 5. validation
print("\n--- validation ---")
report = []
def check(name, a, b, tol=0.005):
    ok = abs(float(a) - float(b)) <= tol
    report.append((name, a, b, ok))
    if not ok:
        fail(f"{name}: {a} vs {b}")

# (a) read-back of every voucher column vs expected
cur.execute(f"SELECT range_id, entry_type, serial_number, {','.join(DB_NAMES)} FROM vouchers")
dbrows = {(r[0], r[1], r[2]): [canon(x) for x in r[3:]] for r in cur.fetchall()}
bad = 0
for rno, rec, _ in live + [(e[0], e[1], e[2]) for e in extra]:
    k = (rec["_range_id"], rec["entry_type"], rec["serial_number"])
    exp = [canon(rec[n]) for n in DB_NAMES]
    if dbrows.get(k) != exp:
        bad += 1
        if bad <= 3:
            diff = [DB_NAMES[i] for i, (a, b) in enumerate(zip(dbrows.get(k, []), exp)) if a != b][:4]
            fail(f"read-back differs for voucher {k}: fields {diff}")
check("vouchers read back identical (all 120 columns): mismatching vouchers", bad, 0, 0)
check("vouchers in DB == Master All + only-older", len(dbrows), len(live) + len(extra), 0)
# (b) every older key is present
miss = [k for k in list(H) + list(Dv) if k not in dbrows]
check("older-export voucher keys missing from vouchers", len(miss), 0, 0)
# (c) items
cur.execute("SELECT COUNT(*) FROM voucher_items"); check("voucher_items in DB == parsed lines", cur.fetchone()[0], len(item_rows), 0)
cur.execute("SELECT v.id FROM vouchers v LEFT JOIN voucher_items i ON i.voucher_id=v.id WHERE i.id IS NULL"); check("vouchers without any item", len(cur.fetchall()), 0, 0)
cur.execute("SELECT i.voucher_id, i.line_no, i.sor_code, i.description, i.work_order_no, i.work_order_date, i.work_start_date, i.work_end_date, i.quantity, i.unit, i.rate, i.amount, i.raw_line FROM voucher_items i ORDER BY i.voucher_id, i.line_no")
dbi = [tuple(canon(x) for x in r) for r in cur.fetchall()]
exp_i = sorted([tuple(canon(x) for x in r) for r in item_rows], key=lambda r: (r[0], r[1]))
check("voucher_items read back identical: differing rows", sum(1 for a, b in zip(dbi, exp_i) if a != b), 0, 0)
# (d) sums of items vs Sub Total
cur.execute("SELECT v.id, v.sub_total, SUM(i.amount) FROM vouchers v JOIN voucher_items i ON i.voucher_id=v.id GROUP BY v.id, v.sub_total")
rows_ = cur.fetchall()
off = [(r[0], float(r[1] or 0), float(r[2] or 0)) for r in rows_ if abs(float(r[1] or 0) - float(r[2] or 0)) >= 0.01]
big = [o for o in off if abs(o[1] - o[2]) >= 1.0]
print(f"  item amounts vs Sub Total: {len(rows_) - len(off)}/{len(rows_)} exact; {len(off)} differ (largest difference {max([abs(a-b) for _, a, b in off] or [0]):.2f}; differences of 1 rupee or more: {len(big)})")
# a SOURCE inconsistency (not a migration error: both sides are verified identical to the sheet) -> reported for review, not a failure
OUT_ITEMS = os.path.join(ROOT, "database", "migration", "step6_item_total_mismatches.csv")
with open(OUT_ITEMS, "w", newline="", encoding="utf-8-sig") as fh:
    w_ = csv.writer(fh)
    w_.writerow(["range", "entry_type", "sr_no", "sum_of_item_amounts", "voucher_sub_total", "difference", "payable_total", "net_amount", "older_export_sub_total", "older_export_item_sum", "note"])
    for vid_, st_, isum_ in big:
        cur.execute("SELECT u.name, v.entry_type, v.serial_number, v.range_id, v.payable_total_amount, v.net_amount FROM vouchers v JOIN users u ON u.id=v.range_id WHERE v.id=%s", (vid_,))
        nm_, et_, sr_, rid_, pay_, net_ = cur.fetchone()
        old_ = H.get((rid_, et_, sr_)) or Dv.get((rid_, et_, sr_))
        o_st = nv(old_[0][1].get("Sub Total")) if old_ else None
        o_is = sum(float(d.get("Amount") or 0) for _, d in old_) if old_ else None
        w_.writerow([nm_, et_, sr_, isum_, st_, round(st_ - isum_, 2), pay_, net_, o_st, o_is, "line items and voucher totals disagree in the source sheet"])
        print(f"  FINDING (source data): {nm_} {et_} Sr {sr_}: items sum {isum_:,.2f} vs Sub Total {st_:,.2f}; older export: Sub Total {o_st}, items {o_is}")
# (e) independent money totals per range x entry type from the staged source
src_tot = collections.defaultdict(lambda: collections.defaultdict(float)); src_cnt = collections.Counter()
for rno, c in rows_ma[1:]:
    c = pad(c, NCOL)
    k = (c[0], c[2]); src_cnt[k] += 1
    for h in ("Sub Total", "Payable Total Amount", "Total Deduction", "Net Amount", "SGST", "CGST", "TDS"):
        v = c[BYHDR[h]["idx"]]
        src_tot[k][h] += v if isinstance(v, (int, float)) else 0
cur.execute("SELECT u.name, v.entry_type, COUNT(*), SUM(v.sub_total), SUM(v.payable_total_amount), SUM(v.total_deduction), SUM(v.net_amount), SUM(v.sgst), SUM(v.cgst), SUM(v.tds) FROM vouchers v JOIN users u ON u.id=v.range_id WHERE v.only_in_older_export=0 GROUP BY u.name, v.entry_type")
groups = bad_groups = 0
gt = collections.defaultdict(float)
for r in cur.fetchall():
    nm = "Division" if r[0] == "SF Sabarkantha Division" else r[0]
    k = (nm, r[1]); groups += 1
    vals = dict(zip(("Sub Total", "Payable Total Amount", "Total Deduction", "Net Amount", "SGST", "CGST", "TDS"), [float(x or 0) for x in r[3:]]))
    if int(r[2]) != src_cnt[k] or any(abs(vals[h] - src_tot[k][h]) > 0.01 for h in vals):
        bad_groups += 1
        fail(f"group {k}: db {r[2]} {vals} vs source {src_cnt[k]} {dict(src_tot[k])}")
    for h in vals: gt[h] += vals[h]
check(f"range x entry-type groups whose count or money differs (of {groups})", bad_groups, 0, 0)
check("GRAND payable total (Master All vouchers)", gt["Payable Total Amount"], sum(v["Payable Total Amount"] for v in src_tot.values()))
check("GRAND net amount (Master All vouchers)", gt["Net Amount"], sum(v["Net Amount"] for v in src_tot.values()))
# (f) 50 random vouchers cell by cell, matched by HEADER NAME against the staged row
random.seed(6)
sample = random.sample(live, 50); mism = 0
for rno, rec, _ in sample:
    c = pad(dict(rows_ma)[rno], NCOL)
    k = (rec["_range_id"], rec["entry_type"], rec["serial_number"])
    cur.execute(f"SELECT {','.join(DB_NAMES)} FROM vouchers WHERE range_id=%s AND entry_type=%s AND serial_number=%s", k)
    row = dict(zip(DB_NAMES, cur.fetchone()))
    for cdef in COLS:
        if cdef["kind"] == "range":
            continue
        src_v = c[cdef["idx"]]
        got = row[cdef["name"]]
        if src_v is None:
            ok = got is None
        elif cdef["kind"] in ("dec", "int"):
            ok = got is not None and abs(float(got) - float(src_v)) < 1e-6
        elif cdef["kind"] in ("date", "datetime"):
            ok = got is not None and canon(got)[:10] == str(src_v)[:10]
        else:
            ok = canon(got) == txt(src_v)
        if not ok:
            mism += 1
            fail(f"sample voucher row {rno} field {cdef['header']}: db {got!r} vs source {src_v!r}")
check("50 random vouchers x 121 fields compared by header name: mismatching cells", mism, 0, 0)
# (g) versions
cur.execute("SELECT version_kind, COUNT(*), SUM(differs_from_live) FROM voucher_versions GROUP BY version_kind")
vs = {r[0]: (r[1], int(r[2])) for r in cur.fetchall()}
check("history00 versions stored", vs.get("history00_snapshot", (0, 0))[0], len(H), 0)
check("division workbook versions stored", vs.get("division_workbook_snapshot", (0, 0))[0], len(Dv), 0)

print()
for n, a, b, ok in report:
    print(f"  {'OK  ' if ok else 'FAIL'} {n:84} {a:>18,.2f} vs {b:>18,.2f}")
print(f"\n  versions that differ from the live voucher: history00 {vs.get('history00_snapshot')} (stored, differing); division {vs.get('division_workbook_snapshot')}")
print("  most frequent changed fields:", [(h, n) for (k, h), n in diff_count.most_common(14)])
print(f"  money-field differences written for your review: {len(money_rows)} cells in {len({(r[0], r[1], r[2]) for r in money_rows})} vouchers -> {OUT_CSV}")
print(f"  live vouchers by source: master_all={len(live)}, only_in_older_export={len(extra)}")
if problems:
    db.rollback()
    print(f"\nROLLED BACK - {len(problems)} problem(s)")
    sys.exit(1)
db.commit()
print("\nCOMMITTED. All checks passed.")
