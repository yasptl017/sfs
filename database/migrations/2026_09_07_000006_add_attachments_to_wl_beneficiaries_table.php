<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wl_beneficiaries', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('link_of_doc');
        });
    }

    public function down(): void
    {
        Schema::table('wl_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};