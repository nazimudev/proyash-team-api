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
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();

            // Photo information
            $table->string('title', 120);
            $table->text('description')->nullable();
            // Gallery category
            $table->string('category', 100);
            // Image storage path
            $table->string('image_path');
            // Activity information
            $table->date('activity_date')->nullable();
            $table->string('alt_text', 200)->nullable();
            // Publication status
            $table->enum('status', ['draft', 'published'])
                ->default('draft');
            // Timestamps
            $table->timestamps();
            // Indexes
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
