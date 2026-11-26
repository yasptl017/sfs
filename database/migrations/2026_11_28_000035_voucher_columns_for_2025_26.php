<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 2025-26 register (Master00 25-26 > Master All) records the treasury result with four columns that the 2026-27 register
 * no longer has: "Pass / Wait for LC / Rejected", "Approval Month", "Cheque No." and "Bank Clearing Date".
 * Kept verbatim (TEXT / date: the vouchers table is near InnoDB's row-size limit). Re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $t) {
            if (! Schema::hasColumn('vouchers', 'pass_status')) {
                $t->text('pass_status')->nullable();          // 'P' = passed by the treasury
            }
            if (! Schema::hasColumn('vouchers', 'approval_month')) {
                $t->text('approval_month')->nullable();       // month the treasury passed it (Apr..Mar)
            }
            if (! Schema::hasColumn('vouchers', 'cheque_no')) {
                $t->text('cheque_no')->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'bank_clearing_date')) {
                $t->date('bank_clearing_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $t) {
            foreach (['pass_status', 'approval_month', 'cheque_no', 'bank_clearing_date'] as $c) {
                if (Schema::hasColumn('vouchers', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
