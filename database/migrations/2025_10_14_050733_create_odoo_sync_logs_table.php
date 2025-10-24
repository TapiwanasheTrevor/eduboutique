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
        Schema::create('odoo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model', 100);
            $table->string('record_id', 100)->nullable();
            $table->string('operation', 50);
            $table->string('direction', 20);
            $table->string('status', 20);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->useCurrent();

            $table->index('model');
            $table->index('status');
            $table->index(['synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odoo_sync_logs');
    }
};
