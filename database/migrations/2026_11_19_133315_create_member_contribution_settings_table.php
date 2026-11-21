<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_contribution_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->decimal('monthly_amount', 12, 2);

            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['member_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_contribution_settings');
    }
};
