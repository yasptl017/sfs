<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the report queries (Allotment vs Expenditure, Summary, Monthly, Form 35 / cash book, voucher lists).
 * Re-runnable (each index is created only when missing).
 */
return new class extends Migration
{
    private array $indexes = [
        'vouchers' => [
            'v_fy_code_range' => ['financial_year_id', 'budget_code', 'range_id'],
            'v_fy_status_date' => ['financial_year_id', 'treasury_status(12)', 'treasury_approval_date'],
            'v_fy_status_month' => ['financial_year_id', 'treasury_status(12)', 'treasury_approval_month(8)'],
            'v_fy_advice' => ['financial_year_id', 'advice_no'],
            'v_fy_range_month' => ['financial_year_id', 'range_id', 'entry_month'],
        ],
        'voucher_versions' => ['vv_voucher' => ['voucher_id']],
        'sor_rates' => ['sor_rates_item_from' => ['sor_item_id', 'effective_from']],
        'sor_items' => ['sor_items_divi_code' => ['divi_code']],
        'tender_items' => ['tender_items_divi_code' => ['divi_code']],
        'estimate_items' => ['estimate_items_divi_range' => ['divi_code', 'range_id']],
        'deduction_cheques' => [],
        'cash_accounts' => ['cash_fy_month' => ['financial_year_id', 'cash_account_month']],
        'd_wager_salaries' => ['dws_join' => ['financial_year_id', 'range_id', 'salary_year']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $list) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();
            foreach ($list as $name => $cols) {
                $plain = array_map(fn ($c) => preg_replace('/\(\d+\)$/', '', $c), $cols);
                if (in_array($name, $existing, true) || ! collect($plain)->every(fn ($c) => Schema::hasColumn($table, $c))) {
                    continue;
                }
                if ($plain === $cols || DB::getDriverName() !== 'mysql') {
                    Schema::table($table, fn (Blueprint $t) => $t->index($plain, $name));
                } else {   // TEXT columns need a prefix length on MariaDB / MySQL
                    $sql = implode(', ', array_map(fn ($c) => preg_match('/^(\w+)\((\d+)\)$/', $c, $m) ? "`{$m[1]}`({$m[2]})" : "`$c`", $cols));
                    DB::statement("CREATE INDEX `$name` ON `$table` ($sql)");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $list) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();
            foreach (array_keys($list) as $name) {
                if (in_array($name, $existing, true)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
