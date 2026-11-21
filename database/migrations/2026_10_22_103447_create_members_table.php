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
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('full_name_bn')->nullable();
            $table->string('full_name_en');
            $table->string('father_name');
            $table->string('mother_name');
            $table->date('date_of_birth');
            $table->string('nationality');
            $table->string('religion')->nullable();
            $table->string('occupation');
            $table->string('gender')->nullable();
            $table->string('marital_status');
            $table->string('educational_qualification');
            $table->string('blood_group')->nullable();
            $table->string('mobile');
            $table->string('profile_photo')->nullable();

            // Permanent address
            $table->string('permanent_village');
            $table->string('permanent_post_office');
            $table->string('permanent_upazila');
            $table->string('permanent_district');

            // Present address
            $table->string('present_village');
            $table->string('present_post_office');
            $table->string('present_upazila');
            $table->string('present_district');

            $table->string('membership_status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
