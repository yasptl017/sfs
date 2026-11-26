<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Step 7b: restore two columns the party screen uses and keep the legacy SF column name. Re-runnable. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('party_registrations', 'approval_attachment')) {
            Schema::table('party_registrations', function (Blueprint $t) {
                $t->string('approval_attachment')->nullable();
                $t->string('contact_attachment')->nullable();
            });
        }
        if (Schema::hasColumn('sf_beneficiaries', 'ha_dcp_plants') && ! Schema::hasColumn('sf_beneficiaries', 'hactor_dcp_plants')) {
            Schema::table('sf_beneficiaries', function (Blueprint $t) {
                $t->renameColumn('ha_dcp_plants', 'hactor_dcp_plants');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sf_beneficiaries', 'hactor_dcp_plants')) {
            Schema::table('sf_beneficiaries', fn (Blueprint $t) => $t->renameColumn('hactor_dcp_plants', 'ha_dcp_plants'));
        }
        if (Schema::hasColumn('party_registrations', 'approval_attachment')) {
            Schema::table('party_registrations', fn (Blueprint $t) => $t->dropColumn(['approval_attachment', 'contact_attachment']));
        }
    }
};
