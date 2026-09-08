<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_codes', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code')->unique();
            $table->string('operating_head')->nullable();
            $table->string('scheme')->nullable();
            $table->string('object_class')->nullable();
            $table->string('object_code')->nullable();
            $table->string('description')->nullable();
            $table->string('item')->nullable();
            $table->string('model')->nullable();
            $table->string('scheme_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_codes');
    }
};
