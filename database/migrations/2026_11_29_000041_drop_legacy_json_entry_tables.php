<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the tables of the first version of the application (vouchers / bills stored as JSON). Every screen now uses the typed
 * tables (vouchers, voucher_items, advices on the vouchers ...). The tables were empty; their models, controllers and views were
 * removed with this migration. A non-empty table is never dropped (the migration stops instead).
 */
return new class extends Migration
{
    private array $tables = ['tender_entries', 'free_entries', 'd_wager_salary_entries', 'd_wager_arrears_entries', 'sf_beneficiary_entries', 'wl_beneficiary_entries',
        'gst_challans', 'bill_advices', 'bill_advice_reports', 'treasury_details', 'change_bill_order_numbers'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && DB::table($t)->exists()) {
                throw new RuntimeException("Legacy table $t is not empty - not dropped.");
            }
        }
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // not restorable: the tables were empty and their code was removed (the original create migrations are in git history)
    }
};
