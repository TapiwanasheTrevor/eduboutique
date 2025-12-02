<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_lead_id')->nullable()->after('notes');
            $table->timestamp('odoo_synced_at')->nullable()->after('odoo_lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_forms', function (Blueprint $table) {
            $table->dropColumn(['odoo_lead_id', 'odoo_synced_at']);
        });
    }
};
