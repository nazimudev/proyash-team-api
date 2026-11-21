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
        Schema::table('contribution_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');

            $table->foreignId('payment_method_id')
                ->after('member_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contribution_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->string('payment_method', 30);
        });
    }
};
