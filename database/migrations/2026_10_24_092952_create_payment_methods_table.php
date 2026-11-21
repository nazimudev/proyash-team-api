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
        Schema::create('payment_methods', function (Blueprint $table) {
                    $table->id();
                    $table->string('name', 100);
                    $table->string('type', 50);
                    $table->string('account_number', 100)->nullable();
                    $table->string('account_holder', 150)->nullable();
                    $table->text('description');
                    $table->json('steps')->nullable();
                    $table->boolean('requires_transaction_id')->default(true);
                    $table->boolean('requires_phone')->default(true);
                    $table->boolean('requires_payment_proof')->default(false);
                    $table->unsignedInteger('display_order')->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                    $table->index(['is_active', 'display_order']);
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
