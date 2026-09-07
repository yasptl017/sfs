<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wl_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('serial_number');
            $table->string('wl_bene_code');
            $table->string('round');
            $table->string('beat');
            $table->string('yojana_name');
            $table->string('full_name');
            $table->string('name_gujarati')->nullable();
            $table->string('mobile_no', 20)->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->string('category', 20)->nullable();
            $table->string('taluka')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('survey_block_no')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_no')->nullable();
            $table->string('gps_n')->nullable();
            $table->string('gps_e')->nullable();
            $table->string('gps')->nullable();
            $table->string('selection_year', 20)->nullable();
            $table->text('remark')->nullable();
            $table->string('link_of_doc')->nullable();
            $table->string('party_status')->default('Active');
            $table->timestamps();
            $table->unique(['range_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wl_beneficiaries');
    }
};