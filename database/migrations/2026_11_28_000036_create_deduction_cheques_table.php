<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cheque register of the monthly advices (sheet "Ded. Cheques" of Master00 25-26): the cheques issued for the amounts
 * deducted from the bills of an advice (GST/IT TDS, labour cess ... remitted to the banks / departments). Re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deduction_cheques')) {
            return;
        }
        Schema::create('deduction_cheques', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->string('advice_month', 10)->nullable();
            $t->unsignedInteger('advice_no')->nullable();
            $t->string('details_of_cheque')->nullable();   // bank
            $t->date('cheque_date')->nullable();
            $t->string('cheque_no', 40)->nullable();
            $t->string('payee')->nullable();
            $t->decimal('amount', 20, 2)->nullable();
            $t->dateTime('source_time_stamp')->nullable();
            $t->string('entered_by')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'advice_no'], 'ded_cheques_fy_advice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_cheques');
    }
};
