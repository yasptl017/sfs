<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('serial_number');
            $table->string('party_code');
            $table->string('round');
            $table->decimal('approved_percent', 8, 2)->nullable();
            $table->string('small_description')->nullable();
            $table->string('party_name');
            $table->string('pan_card_no')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('bank_name');
            $table->string('account_no')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('branch')->nullable();
            $table->decimal('deduction_sgst', 8, 2)->nullable();
            $table->decimal('deduction_cgst', 8, 2)->nullable();
            $table->decimal('deduction_igst', 8, 2)->nullable();
            $table->decimal('deduction_labour_cess', 8, 2)->nullable();
            $table->decimal('deposit_deduction', 8, 2)->nullable();
            $table->decimal('tds', 8, 2)->nullable();
            $table->string('party_approval_no')->nullable();
            $table->string('party_aadhaar_no')->nullable();
            $table->string('party_mobile_no')->nullable();
            $table->string('party_email')->nullable();
            $table->string('link_of_doc')->nullable();
            $table->text('party_address')->nullable();
            $table->string('party_status')->default('Active');
            $table->timestamps();

            $table->unique(['range_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_registrations');
    }
};
