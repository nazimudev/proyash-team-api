
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_agreements', function (Blueprint $table) {
            $table->id();

            // Member Relationship
            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            // Agreement Information
            $table->string('agreement_type', 50);
            $table->string('version', 30)->default('v1.0');

            // Acceptance Record
            $table->timestamp('accepted_at');

            $table->timestamps();

            // Prevent duplicate agreement version
            $table->unique(
                ['member_id', 'agreement_type', 'version'],
                'member_agreements_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_agreements');
    }
};
