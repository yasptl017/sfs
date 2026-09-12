<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('allotments_from_circle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('budget_code_id')->constrained()->restrictOnDelete();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allotments_from_circle');
    }
};
