<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * District-wise allotment entries typed on the sheet "Allotment Entry for Summary" (SK = Sabarkantha, AR = Arvalli):
 * how much of a budget code's allotment belongs to a district, per entry date. Used by the district-wise ("New") Summary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_allotments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->string('budget_code', 20);
            $t->string('scheme')->nullable();
            $t->string('model', 500)->nullable();
            $t->string('district', 10)->nullable(); // two V176 entries have no district typed in the sheet
            $t->date('entry_date');
            $t->decimal('amount', 20, 2);
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->unsignedSmallInteger('source_col')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'budget_code', 'district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_allotments');
    }
};
