<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lossless staging of every spreadsheet row (migration Step 3).
 * One row per non-empty sheet row, cells kept verbatim as typed JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('source_file');
            $table->char('source_sha256', 64);
            $table->unsignedSmallInteger('sheet_order');
            $table->string('sheet_name');
            $table->string('sheet_state', 20);
            $table->unsignedInteger('expected_rows');
            $table->unsignedInteger('expected_cells');
            $table->unsignedSmallInteger('max_col');
            $table->char('expected_rows_sha256', 64);
            $table->unsignedInteger('loaded_rows')->nullable();
            $table->char('loaded_rows_sha256', 64)->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['source_file', 'sheet_name']);
        });

        Schema::create('staging_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staging_sheet_id')->constrained('staging_sheets')->cascadeOnDelete();
            $table->unsignedInteger('row_no');
            $table->longText('cells'); // raw JSON array, kept as exact text
            $table->unique(['staging_sheet_id', 'row_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_rows');
        Schema::dropIfExists('staging_sheets');
    }
};
