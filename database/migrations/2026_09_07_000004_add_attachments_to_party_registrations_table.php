<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_registrations', function (Blueprint $table) {
            $table->string('approval_attachment')->nullable()->after('party_approval_no');
            $table->string('contact_attachment')->nullable()->after('party_email');
        });
    }

    public function down(): void
    {
        Schema::table('party_registrations', function (Blueprint $table) {
            $table->dropColumn(['approval_attachment', 'contact_attachment']);
        });
    }
};
