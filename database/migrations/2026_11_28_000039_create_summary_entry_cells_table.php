<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly manual entries behind the Summary reports (Master00 sheets "Summary Entry" and "New Summary Entry"): for every budget code
 * (and district in the new sheet) and month the Target, Achieved Target, Pending Vouchers, Change in Pending Vouchers, Change in
 * Requirement and Remarks. Kept cell by cell (one row per non-empty cell), so nothing typed in the sheet is lost. Re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('summary_entry_cells')) {
            return;
        }
        Schema::create('summary_entry_cells', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->string('sheet', 30);                       // 'Summary Entry' | 'New Summary Entry'
            $t->string('budget_code', 20)->nullable();
            $t->string('scheme')->nullable();
            $t->string('model', 500)->nullable();
            $t->string('district', 10)->nullable();        // New Summary Entry only (SK / AR)
            $t->string('month', 3)->nullable();            // Apr..Mar (block of the sheet)
            $t->string('field')->nullable();               // column heading: Target, Achieved Target, ...
            $t->decimal('value_num', 20, 4)->nullable();
            $t->text('value_text')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row');
            $t->unsignedSmallInteger('source_col');
            $t->timestamps();
            $t->index(['financial_year_id', 'sheet', 'budget_code', 'month'], 'summary_entry_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_entry_cells');
    }
};
