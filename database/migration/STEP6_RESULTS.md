# Step 6 - vouchers (validated)

Generator `database/tools/gen_6.py` (spec `step6_spec.json`) -> migration `2026_11_24_000031_step6_vouchers.php`; loader `database/tools/load_6.py`.

| Table | Rows | Content |
|---|---:|---|
| vouchers | 7,572 | 7,565 from Master00 `Master All` (live) + 7 imported from older exports, flagged `only_in_older_export = 1` |
| voucher_items | 15,773 | 15,764 parsed from `Item Details` + 9 lines of the 7 older-only vouchers; each keeps `raw_line` (original JSON) |
| voucher_versions | 3,696 | 3,402 History00 (15 Aug) + 294 Division workbook (6 Nov) copies; 2,278 + 180 differ from live (`diff_fields` lists which) |

Voucher key: (financial year, range, entry type, Sr. No.) - unique. 6 entry types: Tender, Free, D.Wagers Salary, D.Wagers Arrears, Bene., WL Bene.

## Validation (all passed)
- All 7,572 vouchers read back identical across all 120 columns; Master All cells accounted for: 121 header + 463,884 mapped = 464,005 staged.
- Count and money (Sub Total, Payable, Total Deduction, Net, SGST, CGST, TDS) identical for all 62 range x entry-type groups; grand payable 343,926,832, net 335,736,168.
- Every voucher key found in History00 (3,402) and Division workbook (294) exists in `vouchers`.
- 50 random vouchers x 121 fields compared by header name against the staged sheet: 0 mismatches.
- Item amounts equal Sub Total for 7,565 of 7,572 vouchers; 6 differ by under a rupee (rounding); 1 is a source inconsistency (below).

## For your review
1. `step6_money_differences.csv`: 575 money cells in 101 vouchers where the 15 Aug History00 copy differs from the live Master All value
   (Master All kept as live per the agreed rule). Net Amount differs on 99 vouchers (net effect -4,17,377; e.g. Khedbrahma Tender 123: 2,75,447 -> 0).
2. `step6_item_total_mismatches.csv`: Modasa D.Wagers Salary Sr 51 - line items sum 30,736 but Sub Total/Payable 27,386 (same in the older export, so long-standing).
3. 2 line items have a Work End Date that is not a date; the original text is kept in `raw_line`.
4. 7 vouchers only in History00 (Idar T-24; Khedbrahma T-117/118/122; Modasa T-74; Prantij T-139; Vadali Bene-11): live, flagged.
