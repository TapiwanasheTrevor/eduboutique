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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_zwl', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->uuid('category_id')->nullable();
            $table->string('syllabus', 50)->default('Other');
            $table->string('level', 50)->default('Primary');
            $table->string('subject', 100)->nullable();
            $table->string('publisher')->nullable();
            $table->string('isbn', 50)->nullable();
            $table->string('author')->nullable();
            $table->text('cover_image')->nullable();
            $table->string('stock_status', 20)->default('in_stock');
            $table->integer('stock_quantity')->default(0);
            $table->boolean('featured')->default(false);
            $table->integer('odoo_product_id')->nullable();
            $table->timestamp('odoo_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->index('category_id');
            $table->index('syllabus');
            $table->index('level');
            $table->index('featured');
            $table->index('odoo_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
