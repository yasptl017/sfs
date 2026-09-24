<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_bill_order_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_type')->default('Simple Receipt');
            $table->string('advice_no');
            $table->date('advice_date');
            $table->string('bill_register_no');
            $table->string('order_outward_no');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'bill_type']);
            $table->index(['division_id', 'advice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_bill_order_numbers');
    }
};
