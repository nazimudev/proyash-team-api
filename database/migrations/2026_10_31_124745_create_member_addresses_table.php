
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_addresses', function (Blueprint $table) {
            $table->id();

            // Member Relationship
            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            // Address Information
            $table->enum('type', ['permanent', 'present']);

            $table->string('village', 150);
            $table->string('post_office', 150);
            $table->string('upazila', 150);
            $table->string('district', 150);

            $table->timestamps();

            // Prevent duplicate address types for same member
            $table->unique(['member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_addresses');
    }
};
