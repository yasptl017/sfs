<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gst_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_register_no');
            $table->string('advice_no');
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->string('gst_challan_no');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'bill_register_no']);
            $table->index(['division_id', 'advice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gst_challans');
    }
};
