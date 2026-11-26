<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Step 5a: financial years, office logins, year-tagged budget codes,
 * year-tagged locations and a generic lookups table.
 * Every migrated row carries staging_sheet_id + source_row (traceable to the spreadsheet).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 9)->unique(); // 2026-27
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('office_code', 20)->nullable()->unique()->after('role');
            $table->boolean('is_active')->default(true)->after('office_code');
        });

        // budget_codes: now per financial year
        Schema::table('budget_codes', function (Blueprint $table) {
            $table->dropUnique(['budget_code']);
        });
        Schema::table('budget_codes', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('id')->constrained('financial_years')->restrictOnDelete();
            $table->decimal('rate', 15, 2)->nullable()->after('scheme_year');
            $table->decimal('target', 15, 2)->nullable()->after('rate');
            $table->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $table->unsignedInteger('source_row')->nullable();
            $table->text('description')->nullable()->change();
            $table->text('item')->nullable()->change();
            $table->text('model')->nullable()->change();
            $table->unique(['financial_year_id', 'budget_code']);
        });

        // range_locations: per financial year, traceable, place may be blank in the source
        Schema::table('range_locations', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('id')->constrained('financial_years')->restrictOnDelete();
            $table->unsignedInteger('sr')->nullable();
            $table->string('source_label', 100)->nullable(); // original spelling of the range name when normalised
            $table->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('round')->nullable()->change();
            $table->string('beat')->nullable()->change();
            $table->string('place', 500)->nullable()->change();
            $table->index(['financial_year_id', 'range_id']);
        });

        // every other list on the DropDown sheets (banks, cheque payees, vehicles, districts, SF models, ...)
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->restrictOnDelete();
            $table->string('list_name');
            $table->unsignedSmallInteger('source_col');
            $table->unsignedInteger('source_row');
            $table->text('value');
            $table->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $table->timestamps();
            $table->index(['financial_year_id', 'list_name']);
            $table->unique(['staging_sheet_id', 'source_col', 'source_row']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookups');
        Schema::table('range_locations', function (Blueprint $table) {
            $table->dropIndex(['financial_year_id', 'range_id']);
            $table->dropConstrainedForeignId('staging_sheet_id');
            $table->dropConstrainedForeignId('financial_year_id');
            $table->dropColumn(['sr', 'source_label', 'source_row']);
        });
        Schema::table('budget_codes', function (Blueprint $table) {
            $table->dropUnique(['financial_year_id', 'budget_code']);
            $table->dropConstrainedForeignId('staging_sheet_id');
            $table->dropConstrainedForeignId('financial_year_id');
            $table->dropColumn(['rate', 'target', 'source_row']);
            $table->unique('budget_code');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['office_code']);
            $table->dropColumn(['office_code', 'is_active']);
        });
        Schema::dropIfExists('financial_years');
    }
};
