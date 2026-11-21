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
        Schema::create('founders', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('full_name_en');
            $table->string('full_name_bn')->nullable();
            $table->string('designation');
            $table->string('photo')->nullable();

            // Professional Information
            $table->text('biography')->nullable();
            $table->text('education')->nullable();
            $table->text('professional_experience')->nullable();
            $table->text('community_involvement')->nullable();

            // Founder's Message
            $table->string('message_heading')->nullable();
            $table->text('message')->nullable();

            // Publication Settings
            $table->enum('status', ['draft', 'published'])
                ->default('draft');

            $table->unsignedInteger('display_order')
                ->default(1)
                ->unique();

            $table->boolean('confirmed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('founders');
    }
};
