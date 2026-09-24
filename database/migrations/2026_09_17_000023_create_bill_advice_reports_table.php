<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_advice_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_register_no');
            $table->string('advice_no');
            $table->string('report_type'); // gst_report, bill_report, deduction_report, range_report
            $table->text('tharav_descriptions')->nullable();
            $table->json('report_data')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'bill_register_no', 'advice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_advice_reports');
    }
};
