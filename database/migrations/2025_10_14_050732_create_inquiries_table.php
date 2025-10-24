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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('inquiry_number', 50)->unique()->index();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 50);
            $table->string('delivery_method', 50)->default('agent_delivery');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->text('message')->nullable();
            $table->json('cart_items')->nullable();
            $table->decimal('total_zwl', 10, 2)->default(0);
            $table->decimal('total_usd', 10, 2)->default(0);
            $table->string('status', 50)->default('pending');
            $table->integer('odoo_order_id')->nullable();
            $table->timestamp('odoo_synced_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index('status');
            $table->index(['created_at']);
            $table->index('odoo_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
