<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_no');
            $table->string('advice_no');
            $table->date('advice_date')->nullable();
            $table->string('bill_type')->nullable(); // Contingency, Simple Receipt, SNA, IFMS
            $table->decimal('bill_amount', 14, 2)->default(0);
            $table->string('treasury_name')->nullable();
            $table->string('token_no')->nullable();
            $table->date('token_date')->nullable();
            $table->string('tv_no')->nullable(); // Treasury Voucher No.
            $table->date('tv_date')->nullable();
            $table->string('payment_ref_no')->nullable();
            $table->string('status')->default('Submitted'); // Submitted, Cleared, Pending, Objected
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'bill_no', 'advice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_details');
    }
};
