<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Net allotment adjustment per budget code, for years where the adjustment ledger (from-code -> to-code transfers) is not
 * available in the source: the 2025-26 workbooks have "Adjusted Allotment" per code in the Budget Allotment sheet, but the
 * ledger itself (History00 > Raw Data of Allotment, Allotment Adjustment table) is #REF! and the Adjustment History pivot is empty.
 * amount = adjusted allotment - circle allotment (positive = received, negative = given). Re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('allotment_net_adjustments')) {
            return;
        }
        Schema::create('allotment_net_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('budget_code_id')->constrained('budget_codes')->restrictOnDelete();
            $t->decimal('amount', 20, 2);
            $t->text('remark')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->unique(['financial_year_id', 'budget_code_id'], 'net_adj_fy_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allotment_net_adjustments');
    }
};
