<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('project_id')->unique()->nullable();

            // Category
            $table->foreignId('category_id')
                ->constrained('project_categories')
                ->restrictOnDelete();

            $table->text('description');

            // Project Media
            $table->string('cover_image')->nullable();

            // Location & Responsibility
            $table->string('location');
            $table->string('responsible_person')->nullable();
            $table->string('contact_phone', 20)->nullable();

            // Project Timeline
            $table->date('start_date');
            $table->date('expected_end_date');

            // Budget & Funding
            $table->decimal('allocated_budget', 15, 2);
            $table->string('funding_source')->nullable();
            $table->text('funding_details')->nullable();

            // Project Status
            $table->enum('status', [
                'planned',
                'in_progress',
                'complete',
                'on_hold',
                'cancelled',
            ])->default('planned');

            // Progress
            $table->unsignedTinyInteger('progress')->default(0);

            // Internal Notes
            $table->text('internal_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
