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
        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('video_url');
            $table->text('thumbnail_url')->nullable();
            $table->string('category', 50)->default('other');
            $table->string('duration', 20)->nullable();
            $table->boolean('published')->default(true);
            $table->integer('views')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
