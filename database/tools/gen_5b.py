"""
Generate (a) database/migration/step5b_spec.json and (b) the Laravel migration for Step 5b
from the real staged headers/values.   python -I -X utf8 database/tools/gen_5b.py
"""
import json
import os
import re
import sys

import pymysql

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from step5b_spec import COMMON, DATE_KEYS, EXACT, NUM_KEYS, SRC, TABLES  # noqa: E402

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))


def env(path):
    out = {}
    for line in open(path, encoding="utf-8"):
        m = re.match(r"^([A-Z_]+)=(.*)$", line.strip())
        if m:
            out[m.group(1)] = m.group(2).strip('"')
    return out


E = env(os.path.join(ROOT, ".env"))
db = pymysql.connect(host=E["DB_HOST"], port=int(E["DB_PORT"]), user=E["DB_USERNAME"], password=E["DB_PASSWORD"], database=E["DB_DATABASE"], charset="utf8mb4")
cur = db.cursor()
DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$")
TEXT_HINT = ("address", "description", "remark", "particular", "details", "item name", "place", "approval", "name of", "names of", "categories", "link")


def staged(src, sheet):
    cur.execute("SELECT id FROM staging_sheets WHERE source_file=%s AND sheet_name=%s", (src, sheet))
    sid = cur.fetchone()[0]
    cur.execute("SELECT row_no, cells FROM staging_rows WHERE staging_sheet_id=%s ORDER BY row_no", (sid,))
    return [(r, json.loads(c)) for r, c in cur.fetchall()]


def slug(h):
    s = re.sub(r"[^a-z0-9]+", "_", h.lower()).strip("_")
    return s[:55].rstrip("_") or "col"


def resolve(h, tnames):
    hl = h.strip().lower()
    for p, n in sorted(tnames.items(), key=lambda kv: -len(kv[0])):
        if hl.startswith(p):
            return n
    if hl in EXACT:
        return EXACT[hl]
    for p, n in COMMON:
        if hl.startswith(p) and (p not in ("sr", "sr.") or hl in ("sr", "sr.")):
            return n
    return slug(h)


spec = []
for t in TABLES:
    tn = t["table"]
    sheets = t["sheets"]
    first = next(iter(sheets.values()))
    hdr_row = staged(*first)[0][1]
    ncols = len(hdr_row)
    values = {i: [] for i in range(ncols)}
    for src, sh in sheets.values():
        for rno, c in staged(src, sh):
            if rno < 2:
                continue
            for i, v in enumerate(c[:ncols]):
                if v is not None:
                    values[i].append(v)
    cols, used = [], set()
    for i, h in enumerate(hdr_row):
        if h is None:
            continue
        h = str(h).strip()
        name = resolve(h, t.get("names", {}))
        if name == "RANGE":
            cols.append(dict(idx=i, header=h, name="range", kind="range"))
            continue
        base, k = name, 2
        while name in used or name in ("id", "financial_year_id", "range_id", "range_name"):
            name = f"{base}_{k}"
            k += 1
        used.add(name)
        vals = values[i]
        hl = h.lower()
        kind, ln = "str", 0
        if vals and name not in ("hactor_dcp_plants", "unit"):
            if name in ("serial_number",) and all(isinstance(v, (int, float)) and not isinstance(v, bool) and v == int(v) for v in vals):
                kind = "int"
            elif name == "times_edited" and all(isinstance(v, (int, float)) and v == int(v) for v in vals):
                kind = "int"
            elif any(k in hl for k in DATE_KEYS) and all(isinstance(v, str) and DATE_RE.match(v) for v in vals):
                kind = "datetime" if any(len(v) > 10 and not v.endswith("00:00:00") for v in vals) else "date"
            elif any(k in hl for k in NUM_KEYS) and all(isinstance(v, (int, float)) and not isinstance(v, bool) for v in vals):
                kind = "dec"
        if kind == "str":
            ln = max([len(str(int(v)) if isinstance(v, float) and v == int(v) else str(v)) for v in vals] or [0])
            if ln > 255 or any(k in hl for k in TEXT_HINT):
                kind = "text"
        cols.append(dict(idx=i, header=h, name=name, kind=kind, maxlen=ln, n=len(vals)))
    spec.append(dict(table=tn, owner=t.get("owner"), keep=t.get("keep", []), key=t["key"], status=t.get("status", False),
                     division_id=(tn == "division_tender_parties"), sheets={fy: list(v) for fy, v in sheets.items()}, columns=cols))

# ---- SOR is special: items + dated rates
spec.append(dict(table="sor_items", special="sor", sheets={fy: [f, "SOR"] for fy, f in SRC.items()}))

os.makedirs(os.path.join(ROOT, "database", "migration"), exist_ok=True)
json.dump(spec, open(os.path.join(ROOT, "database", "migration", "step5b_spec.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)


# ---------------- migration php ----------------
def php_col(c):
    n, k = c["name"], c["kind"]
    if k == "str":
        return f"$t->string('{n}', 255)->nullable();"
    if k == "text":
        return f"$t->text('{n}')->nullable();"
    if k == "int":
        return f"$t->bigInteger('{n}')->nullable();"
    if k == "dec":
        return f"$t->decimal('{n}', 20, 6)->nullable();"
    if k == "date":
        return f"$t->date('{n}')->nullable();"
    if k == "datetime":
        return f"$t->dateTime('{n}')->nullable();"
    raise ValueError(c)


out = []
for s in spec:
    if s.get("special"):
        continue
    L = [f"        Schema::create('{s['table']}', function (Blueprint $t) {{", "            $t->id();",
         "            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();"]
    if s["owner"] == "range":
        L += ["            $t->foreignId('range_id')->nullable()->constrained('users')->nullOnDelete();", "            $t->string('range_name', 100)->nullable();"]
    if s["division_id"]:
        L += ["            $t->foreignId('division_id')->nullable()->constrained('users')->nullOnDelete();"]
    for c in s["columns"]:
        if c["kind"] == "range":
            continue
        L.append("            " + php_col(c))
    if "attachments" in s["keep"]:
        L.append("            $t->json('attachments')->nullable();")
    if s["status"] and not any(c["name"] == "party_status" for c in s["columns"]):
        L.append("            $t->string('party_status')->default('Active');")
    L += ["            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();",
          "            $t->unsignedInteger('source_row')->nullable();", "            $t->timestamps();"]
    idx = ["'financial_year_id'"] + (["'range_id'"] if s["owner"] == "range" else [])
    L.append(f"            $t->index([{', '.join(idx)}]);")
    if any(c["name"] == "serial_number" for c in s["columns"]):
        L.append("            $t->index(['financial_year_id', 'serial_number']);")
    L.append("        });")
    out.append("\n".join(L))

sor = """        Schema::create('sor_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('range_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('range_name', 100)->nullable();
            $t->string('tender_code')->nullable();
            $t->string('divi_code')->nullable();
            $t->bigInteger('serial_number')->nullable();
            $t->text('particular')->nullable();
            $t->string('unit')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'divi_code']);
        });
        Schema::create('sor_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sor_item_id')->constrained('sor_items')->cascadeOnDelete();
            $t->date('effective_from');
            $t->decimal('rate', 20, 6);
            $t->unsignedSmallInteger('source_col');
            $t->unique(['sor_item_id', 'effective_from']);
        });"""

tables = [s["table"] for s in spec if not s.get("special")] + ["sor_rates", "sor_items"]
php = """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

/**
 * Migration Step 5b - GENERATED by database/tools/gen_5b.py from database/migration/step5b_spec.json.
 * Replaces the old (empty / test-only) party and beneficiary tables with typed, year-tagged tables and adds the
 * rate-list and register tables. Every row keeps staging_sheet_id + source_row.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['party_registrations', 'division_tender_parties', 'sf_beneficiaries', 'wl_beneficiaries'] as $old) {
            Schema::dropIfExists($old);
        }

""" + "\n\n".join(out) + "\n\n" + sor + """
    }

    public function down(): void
    {
        foreach ([""" + ", ".join(f"'{t}'" for t in tables) + """] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
"""
open(os.path.join(ROOT, "database", "migrations", "2026_11_24_000029_step5b_rate_lists_and_registers.php"), "w", encoding="utf-8").write(php)

for s in spec:
    if s.get("special"):
        continue
    ks = {}
    for c in s["columns"]:
        ks[c["kind"]] = ks.get(c["kind"], 0) + 1
    print(f"{s['table']:26} cols={len(s['columns']):3} kinds={ks}")
print("\nnon-string columns:")
for s in spec:
    if s.get("special"):
        continue
    print(" ", s["table"], [f"{c['name']}:{c['kind']}" for c in s["columns"] if c["kind"] not in ("str", "text")])
