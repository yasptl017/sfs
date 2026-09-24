<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->string('advice_no');
            $table->date('advice_date');
            $table->string('bill_type');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedInteger('total_bills_count')->default(0);
            $table->json('selected_entries');
            $table->string('status')->default('Processed');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'bill_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_advices');
    }
};
