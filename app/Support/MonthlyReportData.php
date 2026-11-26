<?php

namespace App\Support;

use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Real data behind the Monthly report (replaces the demo datasets). Population = vouchers APPROVED by the treasury within the
 * selected month (treasury approval date), optionally filtered by payment mode and bill types (IFMS) / schemes (SNA).
 *   gross = payable total      GST TDS = deduction SGST + CGST + IGST      IT TDS = TDS      NPS = CPF contribution
 */
class MonthlyReportData
{
    /** @param array<int, string> $selected */
    public static function build(int $fy, string $month, string $mode, array $selected, string $reportType): array
    {
        self::$salarySheets = [];   // loaded fresh for every report
        $w = ExpenditureReport::window($fy, $month);
        $yearly = $reportType === 'deduction_reports_yearly';
        $from = $yearly ? ((int) substr(FinancialYear::findOrFail($fy)->code, 0, 4)).'-04-01' : $w['start'];
        $idx = array_search($month, ExpenditureReport::MONTHS, true);
        $monthNames = $yearly ? array_slice(ExpenditureReport::MONTHS, 0, ($idx === false ? 0 : $idx) + 1) : [$month];
        $v = self::vouchers($fy, $from, $w['end'], $mode, $selected, $monthNames);
        $salary = $v->where('entry_type', 'D.Wagers Salary')->values();
        $label = Carbon::parse($w['start'])->format('M-Y');
        $d = fn ($date) => $date ? Carbon::parse($date)->format('d/m/Y') : '';

        $bills = [];
        foreach ($v->groupBy(fn ($r) => $r->advice_no.'|'.$r->budget_code)->sortKeys(SORT_NATURAL) as $rows) {
            $f = $rows->first();
            $bills[] = [
                'id' => count($bills) + 1, 'advice_no' => $f->advice_no, 'bill_register_no' => $f->bill_register_no, 'order_outward_no' => $f->bill_order_outward_no,
                'bill_type' => $f->ifms_bill_type, 'payment_mode' => $f->payment_mode, 'budget_code' => $f->budget_code, 'scheme' => $f->scheme,
                'gross_amount' => (float) $rows->sum('payable_total_amount'), 'gst_tds' => (float) $rows->sum(fn ($r) => $r->deduction_sgst + $r->deduction_cgst + $r->deduction_igst),
                'it_tds' => (float) $rows->sum('tds'), 'labour_cess' => (float) $rows->sum('deduction_labour_cess'), 'pt' => (float) $rows->sum('p_tax'), 'gpf' => (float) $rows->sum('gpf'),
                'nps' => (float) $rows->sum('cpf'), 'total_deductions' => (float) $rows->sum('total_deduction'), 'net_amount' => (float) $rows->sum('net_amount'),
                'date' => $d($f->advice_date), 'voucher_no' => $f->treasury_voucher_no, 'epayment_code' => $f->epayment_code,
            ];
        }
        $col = fn (string $k) => array_sum(array_column($bills, $k));

        return [
            'bills' => $bills,
            'totals' => ['count' => count($bills), 'gross' => $col('gross_amount'), 'deductions' => $col('total_deductions'), 'net' => $col('net_amount'), 'gst_tds' => $col('gst_tds'),
                'it_tds' => $col('it_tds'), 'labour_cess' => $col('labour_cess'), 'gpf' => $col('gpf'), 'nps' => $col('nps'), 'pt' => $col('pt')],
            'd36_sections' => self::form53($fy, $month, $mode),
            'epayment_items' => self::epayments($v, $d),
            'tds_items' => self::partyDeductions($v, 'tds', 'taxable_amount', 'tds_amount', 'FORESTRY WORKS'),
            'labour_cess_items' => self::partyDeductions($v, 'deduction_labour_cess', 'taxable_amount', 'cess_amount', 'LABOUR CESS ON FORESTRY WORKS'),
            'ptax_items' => self::salaryList($salary, 'p_tax', fn ($r, $n) => ['sr_no' => $n, 'range' => $r->range_name, 'name' => $r->party_name, 'salary_month' => $r->first_label ?: $label,
                'gross_salary' => (float) $r->payable_total_amount, 'pt_recovered' => (float) $r->p_tax, 'voucher_no' => $r->voucher_no_final]),
            'gpf_items' => self::salaryList($salary, 'gpf', fn ($r, $n) => ['sr_no' => $n, 'name' => $r->party_name, 'amount' => (float) $r->gpf, 'period' => $r->first_label ?: $label, 'voucher_no' => $r->voucher_no_final, 'scheme' => $r->scheme]),
            'nps_items' => self::salaryList($salary, 'cpf', fn ($r, $n) => ['sr_no' => $n, 'name' => $r->party_name, 'amount' => (float) $r->cpf, 'period' => $r->first_label ?: $label, 'voucher_no' => $r->voucher_no_final, 'scheme' => $r->scheme]),
            'pay_slips_list' => $salary->values()->map(fn ($r, $i) => [
                'sr_no' => $i + 1, 'employee_name' => $r->party_name, 'range' => $r->range_name, 'designation' => 'Daily Wager', 'days_worked' => ($sheet = self::salarySheetRow($fy, $r))?->working_days === null ? null : (float) $sheet->working_days, 'daily_rate' => null,
                'leaves' => $sheet?->no_of_leaves === null ? null : (float) $sheet->no_of_leaves, 'basic_pay' => $sheet?->basic_pay === null ? null : (float) $sheet->basic_pay, 'pay_scale' => $sheet?->pay_scale,
                'gross_wage' => (float) $r->payable_total_amount, 'gpf' => (float) $r->gpf, 'nps' => (float) $r->cpf, 'pt' => (float) $r->p_tax, 'total_deductions' => (float) $r->total_deduction,
                'net_payable' => (float) $r->net_amount, 'voucher_no' => $r->voucher_no_final, 'month' => $r->first_label ?: $label,
            ])->all(),
            'challan_items' => self::challans($fy, $month),
        ];
    }

    /** @return Collection<int, object> */
    public static function vouchers(int $fy, string $from, string $to, string $mode, array $selected, array $monthNames = []): Collection
    {
        $q = DB::table('vouchers as v')->join('users as u', 'u.id', '=', 'v.range_id')
            ->leftJoin('budget_codes as b', fn ($j) => $j->on('b.budget_code', '=', 'v.budget_code')->on('b.financial_year_id', '=', 'v.financial_year_id'))
            ->where('v.financial_year_id', $fy)->where('v.treasury_status', 'Approved')
            ->where(function ($q) use ($from, $to, $monthNames) {
                // years without treasury dates (2025-26) are passed by their Approval Month
                $q->whereBetween('v.treasury_approval_date', [$from, $to])
                    ->orWhere(fn ($q2) => $q2->whereNull('v.treasury_approval_date')->whereIn('v.treasury_approval_month', $monthNames ?: ['']));
            })
            ->selectRaw('v.*, u.name AS range_name, b.operating_head, (SELECT i.sor_code FROM voucher_items i WHERE i.voucher_id = v.id AND i.line_no = 1) AS first_label');
        if (in_array($mode, ['IFMS', 'SNA', 'PFMS'], true)) {
            $q->where('v.payment_mode', $mode);
        }
        if ($selected) {
            if ($mode === 'SNA') {
                $q->whereIn('v.scheme', $selected);
            } elseif (DB::table('vouchers')->where('financial_year_id', $fy)->whereNotNull('ifms_bill_type')->exists()) {
                $q->whereIn('v.ifms_bill_type', $selected);   // years that do not record the IFMS bill type (2025-26) are not filtered by it
            }
        }

        return $q->orderBy('v.advice_no')->orderBy('v.range_id')->orderBy('v.entry_type')->orderBy('v.serial_number')->get();
    }

    private static function epayments(Collection $v, \Closure $d): array
    {
        $out = [];
        foreach ($v->groupBy(fn ($r) => $r->advice_no.'|'.$r->treasury_voucher_no.'|'.$r->epayment_code)->sortKeys(SORT_NATURAL) as $rows) {
            $f = $rows->first();
            $parties = $rows->pluck('party_name')->unique();
            $out[] = ['sr_no' => count($out) + 1, 'head' => $f->operating_head, 'bill_no' => $f->bill_register_no, 'treasury_vr_no' => $f->treasury_voucher_no, 'approval_date' => $d($f->treasury_approval_date),
                'epayment_code' => $f->epayment_code, 'payable_total' => (float) $rows->sum('payable_total_amount'), 'net_amount' => (float) $rows->sum('net_amount'),
                'party_name' => $parties->count() === 1 ? $parties->first() : $parties->count().' parties'];
        }

        return $out;
    }

    /** One line per party with a deduction (TDS / labour cess): taxable value = bill value before GST. */
    private static function partyDeductions(Collection $v, string $deductionCol, string $baseKey, string $amountKey, string $particular): array
    {
        $city = self::cityByParty();
        $out = [];
        foreach ($v->where('entry_type', '!=', 'D.Wagers Salary')->filter(fn ($r) => (float) $r->{$deductionCol} > 0)->groupBy(fn ($r) => mb_strtoupper(trim($r->party_name)).'|'.$r->pan_card_no)->sortKeys() as $rows) {
            $f = $rows->first();
            $out[] = ['sr_no' => count($out) + 1, 'party_name' => $f->party_name, 'resident' => $city[$f->range_id.'|'.$f->party_code] ?? '', 'particular' => $particular,
                $baseKey => (float) $rows->sum(fn ($r) => $r->amount_as_per_approved_rate ?? $r->sub_total), 'pan' => $f->pan_card_no, $amountKey => (float) $rows->sum($deductionCol),
                'voucher_no' => $rows->pluck('voucher_no_final')->filter()->unique()->sort(SORT_NATURAL)->implode(', ')];
        }

        return $out;
    }

    /** @var array<int, array<string, object>> */
    private static array $salarySheets = [];

    /**
     * The row of the "D.Wager Salary" register that belongs to a salary voucher: same range, same D.Wager code (party code) and the
     * month / year named in the voucher description ("... April-2025 ..."). Null when the register has no such row (years without the register).
     */
    private static function salarySheetRow(int $fy, object $voucher): ?object
    {
        if (! isset(self::$salarySheets[$fy])) {
            self::$salarySheets[$fy] = [];
            foreach (DB::table('d_wager_salaries')->where('financial_year_id', $fy)->whereNotNull('d_wager_code')->whereNotNull('salary_year')->whereNotNull('salary_month')->orderBy('id')->get() as $r) {
                self::$salarySheets[$fy][$r->range_id.'|'.$r->d_wager_code.'|'.(int) $r->salary_year.'|'.mb_strtolower(trim($r->salary_month))] ??= $r;
            }
        }
        if (! preg_match('/([A-Za-z]+)[- ]+(\d{4})\s/', (string) $voucher->description, $m)) {
            return null;
        }

        return self::$salarySheets[$fy][$voucher->range_id.'|'.$voucher->party_code.'|'.(int) $m[2].'|'.mb_strtolower($m[1])] ?? null;
    }

    private static function salaryList(Collection $salary, string $col, \Closure $row): array
    {
        $out = [];
        foreach ($salary->filter(fn ($r) => (float) $r->{$col} > 0) as $r) {
            $out[] = $row($r, count($out) + 1);
        }

        return $out;
    }

    /** Recoveries remitted by challan in the month (cash account of the division). */
    private static function challans(int $fy, string $month): array
    {
        $w = ExpenditureReport::window($fy, $month);
        $label = Carbon::parse($w['start'])->format('M-y');
        $out = [];
        foreach (DB::table('cash_accounts')->where('financial_year_id', $fy)->where('cash_account_month', $label)->whereNotNull('amount')->where('amount', '>', 0)->orderBy('bank_deposit_date')->orderBy('id')->get() as $r) {
            $out[] = ['sr_no' => count($out) + 1, 'date' => $r->recovery_date ? Carbon::parse($r->recovery_date)->format('d/m/Y') : '', 'challan_no' => $r->challan_no, 'treasury' => $r->bank_name_branch,
                'remitted_by' => $r->designation_of_recoverer, 'recovery_details' => trim(($r->recovery_type ? $r->recovery_type.': ' : '').($r->details_of_recovery ?? '')), 'amount' => (float) $r->amount,
                'vr_month' => $r->bank_deposit_date ? Carbon::parse($r->bank_deposit_date)->format('m/Y') : ''];
        }

        return $out;
    }

    /** "range|party code" => city (last part of the registered address). */
    private static function cityByParty(): array
    {
        $map = [];
        foreach (DB::table('party_registrations')->whereNotNull('party_address')->get(['range_id', 'party_code', 'party_address']) as $p) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $p->party_address))));
            $map[$p->range_id.'|'.$p->party_code] = $parts ? end($parts) : '';
        }

        return $map;
    }

    /** Form No. 53 (Abstract / D-36): expenditure up to last month and during the month per demand > minor head > scheme > object of expenditure. */
    private static function form53(int $fy, string $month, string $mode): array
    {
        $cfg = config('form53');
        $rows = ExpenditureReport::codeRows($fy, $month, in_array($mode, ['IFMS', 'SNA', 'PFMS'], true) ? $mode : 'ALL');
        $tree = [];
        foreach ($rows as $r) {
            if ($r->p_exp == 0 && $r->c_exp == 0) {
                continue;
            }
            $key = explode(' ', (string) $r->operating_head)[0];
            $map = $cfg['schemes'][$r->scheme] ?? null;
            [$minorName, $minorCode, $subName, $subCode] = $map ?? [$cfg['other_minor'][0], $cfg['other_minor'][1], $r->scheme, ''];
            $rank = $map ? array_search($r->scheme, array_keys($cfg['schemes']), true) : 1000;
            $leaf = &$tree[$key][$minorName]['subs'][$subName]['leaves'][$r->object_code];
            $leaf['name'] = $leaf['name'] ?? $r->object_code.' '.trim(preg_replace('/^\d+\s*-?\s*/', '', (string) $r->description));
            $leaf['last_month'] = ($leaf['last_month'] ?? 0) + $r->p_exp;
            $leaf['during_month'] = ($leaf['during_month'] ?? 0) + $r->c_exp;
            unset($leaf);
            $tree[$key][$minorName]['code'] = $minorCode;
            $tree[$key][$minorName]['rank'] = min($tree[$key][$minorName]['rank'] ?? 9999, $rank);
            $tree[$key][$minorName]['subs'][$subName]['code'] = $subCode;
            $tree[$key][$minorName]['subs'][$subName]['rank'] = $rank;
        }

        $demands = [];
        $grand = ['l' => 0.0, 'd' => 0.0];
        foreach ($cfg['order'] as $key) {
            if (empty($tree[$key])) {
                continue;
            }
            $dm = $cfg['demands'][$key];
            $minors = $tree[$key];
            uasort($minors, fn ($a, $b) => $a['rank'] <=> $b['rank']);
            $dl = $dd = 0.0;
            $outMinors = [];
            foreach ($minors as $minorName => $m) {
                $subs = $m['subs'];
                uasort($subs, fn ($a, $b) => $a['rank'] <=> $b['rank']);
                $ml = $md = 0.0;
                $outSubs = [];
                foreach ($subs as $subName => $s) {
                    ksort($s['leaves'], SORT_NATURAL);
                    $objects = [];
                    $sl = $sd = 0.0;
                    foreach ($s['leaves'] as $leaf) {
                        $objects[] = ['name' => $leaf['name'], 'last_month' => $leaf['last_month'], 'during_month' => $leaf['during_month'], 'progressive' => $leaf['last_month'] + $leaf['during_month']];
                        $sl += $leaf['last_month'];
                        $sd += $leaf['during_month'];
                    }
                    $outSubs[] = ['sr_no' => count($outSubs) + 1, 'name' => $subName, 'code' => $s['code'], 'objects' => $objects, 'total_last_month' => $sl, 'total_during_month' => $sd, 'total_progressive' => $sl + $sd];
                    $ml += $sl;
                    $md += $sd;
                }
                $outMinors[] = ['name' => $minorName, 'code' => $m['code'], 'sub_heads' => $outSubs, 'total_last_month' => $ml, 'total_during_month' => $md, 'total_progressive' => $ml + $md];
                $dl += $ml;
                $dd += $md;
            }
            $demands[] = $dm + ['sector' => $cfg['sector'], 'sub_sector' => $cfg['sub_sector'], 'sub_major_head' => $cfg['sub_major_head'], 'minor_heads' => $outMinors,
                'total_last_month' => $dl, 'total_during_month' => $dd, 'total_progressive' => $dl + $dd];
            $grand['l'] += $dl;
            $grand['d'] += $dd;
        }

        return ['demands' => $demands, 'grand_total_last_month' => $grand['l'], 'grand_total_during_month' => $grand['d'], 'grand_total_progressive' => $grand['l'] + $grand['d']];
    }
}
