<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('d_wager_salary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('serial_number');
            $table->json('data');
            $table->timestamps();
            $table->unique(['range_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('d_wager_salary_entries');
    }
};
