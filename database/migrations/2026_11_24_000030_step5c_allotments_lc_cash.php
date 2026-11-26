<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Step 5c: allotment ledgers, L.C. entries, cash account (credit data).
 *
 * Semantics proven against the spreadsheets (see database/migration/STEP5C_RESULTS.md):
 *  - allotments_from_circle: each row is a CUMULATIVE restatement; the current allotment of a budget code is the LATEST row.
 *  - allotments_to_range:    each row is an INCREMENT (may be negative); the allotment of (code, range) is the SUM of rows.
 *  - allotment_adjustments:  amount moves from `from` code to `to` code; adjusted allotment = latest circle + adjustments in - out.
 * `ledger_sr` is the sheet's own counter and is NOT unique (two users can enter at once).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['allotments_from_circle', 'allotments_to_range', 'allotment_adjustments', 'lc_entries', 'cash_accounts'] as $old) {
            Schema::dropIfExists($old);
        }

        if (! Schema::hasColumn('budget_codes', 'payment_mode')) { // re-runnable: MariaDB DDL is not transactional
            Schema::table('budget_codes', function (Blueprint $t) {
                $t->string('payment_mode', 20)->nullable()->after('scheme_year');
                $t->string('wing', 50)->nullable()->after('payment_mode');
                $t->string('major_head', 20)->nullable()->after('wing');
                $t->string('demand_no', 20)->nullable()->after('major_head');
            });
        }

        Schema::create('allotments_from_circle', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('budget_code_id')->constrained('budget_codes')->restrictOnDelete();
            $t->unsignedInteger('ledger_sr')->nullable();
            $t->decimal('rate', 20, 6)->nullable();
            $t->decimal('target', 20, 6)->nullable();
            $t->decimal('amount', 20, 2);
            $t->dateTime('allotment_date')->nullable();
            $t->dateTime('source_time_stamp')->nullable();
            $t->string('entered_by')->nullable();
            $t->unsignedInteger('times_edited')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'budget_code_id', 'source_time_stamp'], 'afc_year_code_ts');
        });

        Schema::create('allotments_to_range', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('range_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('budget_code_id')->constrained('budget_codes')->restrictOnDelete();
            $t->unsignedInteger('ledger_sr')->nullable();
            $t->decimal('target', 20, 6)->nullable();
            $t->decimal('amount', 20, 2);
            $t->text('remark')->nullable();
            $t->dateTime('source_time_stamp')->nullable();
            $t->string('entered_by')->nullable();
            $t->unsignedInteger('times_edited')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'budget_code_id', 'range_id'], 'atr_year_code_range');
        });

        Schema::create('allotment_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('from_budget_code_id')->constrained('budget_codes')->restrictOnDelete();
            $t->foreignId('to_budget_code_id')->constrained('budget_codes')->restrictOnDelete();
            $t->unsignedInteger('ledger_sr')->nullable();
            $t->decimal('amount', 20, 2);
            $t->text('remark')->nullable();
            $t->dateTime('source_time_stamp')->nullable();
            $t->string('entered_by')->nullable();
            $t->unsignedInteger('times_edited')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
        });

        Schema::create('lc_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('serial_number')->nullable();
            $t->string('scheme');
            $t->string('class', 50);
            $t->date('entry_date');
            $t->decimal('amount', 20, 2);
            $t->dateTime('source_time_stamp')->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'scheme', 'class']);
        });

        Schema::create('cash_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->foreignId('division_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('range_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('range_name', 100)->nullable();
            $t->unsignedInteger('serial_number')->nullable();
            $t->string('cash_account_month', 20)->nullable();
            $t->string('designation')->nullable();
            $t->string('round_range')->nullable();
            $t->text('details_of_recovery')->nullable();
            $t->string('designation_of_recoverer')->nullable();
            $t->string('recovery_officer_name')->nullable();
            $t->string('recovery_type')->nullable();
            $t->date('recovery_date')->nullable();
            $t->date('bank_deposit_date')->nullable();
            $t->string('bank_name_branch')->nullable();
            $t->string('challan_no')->nullable();
            $t->decimal('amount', 20, 2)->nullable();
            $t->foreignId('staging_sheet_id')->nullable()->constrained('staging_sheets')->nullOnDelete();
            $t->unsignedInteger('source_row')->nullable();
            $t->timestamps();
            $t->index(['financial_year_id', 'range_id', 'cash_account_month'], 'cash_year_range_month');
        });
    }

    public function down(): void
    {
        foreach (['cash_accounts', 'lc_entries', 'allotment_adjustments', 'allotments_to_range', 'allotments_from_circle'] as $table) {
            Schema::dropIfExists($table);
        }
        if (Schema::hasColumn('budget_codes', 'payment_mode')) {
            Schema::table('budget_codes', function (Blueprint $t) {
                $t->dropColumn(['payment_mode', 'wing', 'major_head', 'demand_no']);
            });
        }
    }
};
