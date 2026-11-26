# Step 8 - the three 25-26 sub-folders of `database/GoogleSheets` (financial year 2025-26)

Added 25 Nov 2026: 20 workbooks in three folders, all of financial year **2025-26**. Until now FY 2025-26 had only master data (allotments of the 26-27 files, L.C., rate lists, parties); it had **no vouchers**. It now has the complete year.

| Folder | Workbooks | What they are |
|---|---|---|
| Division Finance System | Master00, History00, Master Data (25-26) | Master00 `Master All` = the 25-26 voucher register (**17,662 vouchers**); History00 = older export + allotment ledger; Master Data = rate lists / parties / allotment targets (**content-identical** to the Master Data 25-26 file already loaded: all 21 sheets have the same SHA-256) |
| Range Finance System | 13 range workbooks (Bayad ... Vijaynagar) | each range's own entry file: entry grid (`1`), `Itemwise` (older snapshot), SF Beneficiary, D.Wager Salary / Arrears, Cash Account, registers |
| DIvision Range Fin. System | Division SFS 25-26 RFS, Report00, ACF, 107 Generator Template | the Division office's own entries; reports + the full allotment ledger; an (almost empty) ACF copy; the setup template (ranges, rounds, beats, places) |

Staged: **824 sheets / 213,009 rows** (`database/tools/export_sheets_folders.py` -> `php artisan sheets:stage --folders`), each sheet re-read from MySQL and its SHA-256 verified. Staging names carry the folder (`Division Finance System/Master00 ...`) so nothing collides with the files loaded before.

## What was loaded (all-or-nothing loaders, read-back verified, cell accounting)
| Data | Table | FY 2025-26 rows | Source | Loader |
|---|---|---|---|---|
| Vouchers + items | `vouchers`, `voucher_items` | 17,704 + 35,965 (17,662 from Master All + 42 only in an older export, flagged) | Master00 25-26 > Master All | `load_6_2526.py` (+ `step6_2526_spec.json`, migration 000035: 4 columns the 25-26 register has) |
| Older copies | `voucher_versions` | 11,056: History00 1,521, Division workbook 356, range workbooks 9,142 (the 8,070 range copies identical to the live voucher are not stored) | History00 / Division / 13 range `Itemwise` | same loader |
| Treasury result | derived columns | 17,135 vouchers `treasury_status = Approved`, `treasury_approval_month` | `Pass = P`, `Approval Month` | `derive_treasury_2526.py` (raw columns kept) |
| Allotments | `allotments_from_circle`, `allotments_to_range`, `allotment_net_adjustments` | 351 / 6,156 / 148 | History00 / Report00 `Raw Data of Allotment`, Budget Allotment | `load_5c_2526.py` (migration 000037) |
| Cash account | `cash_accounts` | 341 (250 from Credit data + 91 only in the Division / range workbooks) | Master00 Credit data, `Cash Account` sheets | `load_5c_2526.py` |
| District allotments | `district_allotments` | 388 | Allotment Entry for Summary | `load_5c_2526.py` |
| Deduction cheques | `deduction_cheques` (new) | 429 | Master00 `Ded. Cheques` | `load_5c_2526.py` (migration 000036) |
| SF beneficiaries | `sf_beneficiaries` | 4,041 | `SF Beneficiary` of the 13 range workbooks (the union sheet of Master Data is `#REF!`) | `load_5b_2526.py` |
| D.Wager pay registers | `d_wager_salaries`, `d_wager_arrears` (new) | 1,558 / 1,448 (+ 24 / 4 of 2026-27 Division) | `D.Wager Salary`, `D.Wager Arrears` | `gen_dwager_pay.py`, `load_dwager_pay.py` (migration 000038) |
| Summary manual entries | `summary_entry_cells` (new) | 9,464 (+ 10,741 of 2026-27) | Master00 `Summary Entry`, `New Summary Entry` | `load_summary_entries.py` (migration 000039) |
| Plantation places | `range_locations` | +27 places only in the Generator Template | `MasterData` | `load_locations_2526.py` |
| Office config | `lookups` | +156 | Master00 `DropDown` | `load_5c_2526.py` |

## Proven against the sheets (FY 2025-26)
- Vouchers: 17,662 / 17,662 read back identical in all 109 columns; money per range x entry type identical; payable 518,951,113, net 505,325,693. No duplicate keys.
- **Passed vouchers** (`Pass = P`) payable **497,365,608** = LC SHEET "Total_Exp" of March = total of the `Ded. Cheques` sheet; **61 of 61 advices**: cheques of the advice = payable total of its passed vouchers.
- Summary sheet (Oct): allotment, P_Exp, C_Exp equal for **270 / 270 budget codes**; LC SHEET (Mar): allotment, P_Exp (397,520,830), C_Exp (99,844,778) equal for **34 / 34 scheme x class rows**.
- Allotments: circle (latest per code) 547,892,647, adjusted 547,892,647, to ranges 538,258,594 - equal to the Budget Allotment sheet for 270 / 270 codes and 3,780 / 3,780 code x range cells. Allotment vs Expenditure `booked` and `entered`: 3,780 / 3,780 cells equal (the sheet excludes the 42 only-in-older vouchers).
- `verify_summary_service_2526.php` 10/10; `verify_reports_2526.php` 25/25 (Summary, Monthly 24 month x mode, Form 35 24 schedules, cash book 12 months, pay slips, registers).
- Registers of the range workbooks (Party Reg., PartyCode, D.Wager Registration, Tender, SOR, Estimate, Bhav Patrak, WL) are proven subsets of Master Data 25-26 (differences are only cosmetic: `2024-07-01 05:30:00` vs `2024-07-01`, `5.0` vs `'5'`). The entry grid `1` of every range workbook = Master All (14 offices, 17,662 vouchers in total, 0 missing, 0 net differences).

## Findings (details and questions: OPEN_POINTS_FOR_VERIFICATION.docx)
1. **Treasury date is not recorded in 2025-26** - only `Approval Month` and `Pass = P`. Reports use the month for this year (`ExpenditureReport::approvedSql`).
2. **Final voucher numbers 2025-26 are plain numbers 1..n per approval month** (no SR-/CONT-/SNA-) and some are shared by several vouchers (223 numbers on 669 vouchers); 25 passed vouchers of December have none. Payment modes are IFMS / **PFMS** (572 vouchers).
3. **Allotment adjustment ledger of 2025-26 is `#REF!`** in the source; only the net per code survives in `Adjusted Allotment` (148 codes, 32,510,979 moved between codes, nets to zero) -> stored as `allotment_net_adjustments`.
4. Budget code **U168** exists only in the allotment ledger (`#N/A`, nets to 0) -> a placeholder code row, hidden from the reports.
5. 7 to-range ledger rows have a physical target but no allotment amount (stored 0.00).
6. **42 vouchers exist only in older range/History00 exports** (not in the current entry grids): flagged `only_in_older_export`; 9 of them (Bayad Free Entry 90-98, advice 51, Rs 218,450) are marked passed in the old copy and are **excluded** from the passed expenditure (the sheets exclude them too).
7. L.C. entries: Sr 53 (120,000, Class-2) typed 2026-10-13 is counted by the sheet as of March 2026 -> the year is a typo of 2025-10-15; with it the year-end "LC in Hand" = **12,451** exactly as on the LC SHEET.
8. 91 cash-account rows (678,260) exist only in the Division / range workbooks, not in Master00 `Credit data`.
9. `D.Wager Salary` register supplies **days worked, leaves, basic pay, D.A., H.R.A.** for the pay slips (all 1,417 salary vouchers of 2025-26 join uniquely by range + D.Wager code + month; 1,311 have identical net).
10. `New Summary Entry` / `Summary Entry`: manual monthly entries (Target, Change in Pending Vouchers, Change in Requirement, Remarks) are now stored; the Summary screens do not use the typed "Change in Requirement" yet (0 in the compared months).

## Sheet coverage (every sheet of the 20 workbooks is either loaded, proven duplicate / derived, or configuration)
- **Loaded**: Master All, Credit data, Ded. Cheques, Allotment Entry for Summary, Summary Entry, New Summary Entry, DropDown (Master00); Itemwise (History00, Division, 13 ranges -> versions); Raw Data of Allotment (History00, Report00); D.Wager Salary / Arrears, Cash Account, SF Beneficiary (Division + ranges); MasterData (Generator Template).
- **Proven covered**: Rejected (103 / 103 rejected vouchers), Budget Allotment copies, Master Data 25-26 (identical), range registers (Party, PartyCode, D.Wager Registration, Tender, SOR, Estimate, Bhav Patrak, WL), entry grid `1`, Report00 `Master All` / `Base Data` (derived cube).
- **Reports / views (derived, not data)**: DashBoard, LC SHEET, Summary, New Summary, Modelwise, Unit Register, Query, Pending Vouchers, Plotwise, Partywise, Yearwise Plot Expe., Detail*, Advice+Range, Range+Month, Credit Data Report, Wingwise, Scheme+Range, Schemewise, Monthwise Expenditure, Party wise Payment, SF/WL Bene. Report, Car History, Dead stock Data, Workwise History, Voucher Report, Detail### (one-voucher print sheets), `ACF` workbook (reports on an empty register).
- **Configuration**: Data Validations, Col Header, IMP DATA, BlankRow, Used Row, Sheet25x, Generator Link / Generator / Round / Beat Registration / Plot List / Other Data (setup; place list loaded).
Everything stays in `staging_rows`, verbatim.

## Screens
- All Vouchers / voucher detail / Registers & Rates / Allotment vs Expenditure / Summary / Monthly (incl. pay slips with days worked) / Form 35 / Cash book now work for 2025-26 through the financial-year switcher. New registers: D.Wager salary register, D.Wager arrears register, Deduction cheques. Payment mode PFMS is offered where it exists.
- Not recorded for 2025-26 (shown as "-" / blank): treasury date, IFMS bill type, bill register no., GST challan, ePayment code.

## Clean-up and performance (27 Nov 2026)
Backup taken first: `e:\Projects\Forest\_backups\sfs_before_cleanup_20261127_024544.sql` (full database, 462 MB) and `my.ini.before_tuning_*` (XAMPP MySQL settings).
- **Removed code** (unrouted, replaced by the voucher / advice modules, referenced only by each other): 12 controllers (Tender / Free / D.Wager Salary / D.Wager Arrears / SF Bene. / WL Bene. entry, Process Bill, GST Challan, Bill-Advice Reports, Treasury Details, Change Bill Order No., Delete Advice), their 11 models, 12 view folders and 11 create-migrations. Migration 000041 drops the 11 tables (all were empty; it refuses to drop a non-empty one). The old URLs still redirect to the new screens.
- **Removed data files**: the JSON-lines exports of the staging step (248 MB in storage/app/private/migration - the rows are in `staging_rows`, hash-verified; `manifest.json` kept so `php artisan sheets:stage --validate-only` still works; re-create with export_sheets*.py), a copy of the initial-logins CSV that was inside the project (the identical copy stays in `_backups`), the log file.
- **Database server (XAMPP MariaDB)**: `innodb_buffer_pool_size` 16 MB -> 512 MB (the tables are about 100 MB without staging, so every report used to read from disk), `tmp_table_size` / `max_heap_table_size` 64 MB - written in C:\xampp\mysql\bin\my.ini and applied live (no restart needed).
- **Indexes** (migration 000040): vouchers (year, budget code, range), (year, treasury status, approval date / month), (year, advice no.), (year, range, entry month); voucher_versions (voucher); sor_rates, sor_items, tender_items, estimate_items (codes); cash_accounts; d_wager_salaries.
- **Queries**: Allotment vs Expenditure rewritten from three correlated sub-queries per code x office (and computed twice) to one grouped pass (15.1 s -> about 0.2 s); Summary / Monthly expenditure per code in one grouped pass; register filter lists select one column (they compared whole rows with DISTINCT); SOR limit rates in one query (212 -> 22 queries); voucher-list status filter without an on-disk temporary table; Form 35 / cash book without loading every line item.
- **Laravel**: optimized class autoloader; route, view and event caches. The configuration is NOT cached on purpose (the tests rely on their own in-memory database settings). After changing routes run `php artisan route:cache` again, or `php artisan optimize:clear`.
- **Timing** (database/tools/perf_pages.php, the 21 heaviest pages of 2026-27): 21.2 s -> about 3 s in total.
