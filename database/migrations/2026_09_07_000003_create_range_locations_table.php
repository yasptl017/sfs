<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('range_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_id')->constrained('users')->cascadeOnDelete();
            $table->string('round');
            $table->string('beat');
            $table->string('place');
            $table->decimal('hectares', 10, 2)->nullable();
            $table->string('place_year', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('range_locations');
    }
};
