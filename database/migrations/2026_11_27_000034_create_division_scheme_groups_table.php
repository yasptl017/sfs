<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Scheme groups the Division merges under one heading in Form No. 35 and the Cash Book (screen "Merge schemes"). Re-runnable. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('division_scheme_groups')) {
            return;
        }
        Schema::create('division_scheme_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $t->string('name');
            $t->text('schemes'); // JSON list of scheme names
            $t->timestamps();
            $t->index('financial_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_scheme_groups');
    }
};
