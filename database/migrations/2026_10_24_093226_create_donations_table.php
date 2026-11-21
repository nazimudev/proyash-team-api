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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
             $table->enum('donor_type', ['named', 'anonymous']);
                    $table->string('full_name', 150)->nullable();
                    $table->decimal('amount', 12, 2);
                    $table->string('purpose')->nullable();
                    $table->foreignId('payment_method_id')
                        ->constrained('payment_methods')
                        ->restrictOnDelete();
                    $table->string('transaction_id', 150);
                    $table->string('phone', 20);
                    $table->string('email')->nullable();
                    $table->text('message')->nullable();
                    $table->enum('status', [
                        'pending',
                        'approved',
                        'rejected'
                    ])->default('pending');
                    $table->foreignId('verified_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                    $table->timestamp('verified_at')->nullable();
                    $table->timestamps();
                    $table->unique([
                        'payment_method_id',
                        'transaction_id'
                    ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
