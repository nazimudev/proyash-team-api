<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contribution_id')
                ->constrained('monthly_contributions')
                ->cascadeOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('payment_method', 30);

            $table->string('transaction_id', 100)->nullable();

            $table->string('receipt_path')->nullable();

            $table->date('payment_date');

            $table->string('status', 20)->default('pending');

            $table->text('notes')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['contribution_id', 'status']);
            $table->index('payment_date');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_payments');
    }
};
