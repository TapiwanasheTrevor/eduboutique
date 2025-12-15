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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract, internship
            $table->string('experience_level')->nullable(); // entry, mid, senior
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->integer('odoo_job_id')->nullable();
            $table->timestamp('odoo_synced_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('department');
            $table->index('odoo_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
