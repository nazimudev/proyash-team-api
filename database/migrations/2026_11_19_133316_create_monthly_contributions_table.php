<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_contributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            // Amount fixed when this monthly contribution is generated
            $table->decimal('required_amount', 12, 2);
            $table->decimal('waived_amount', 12, 2)->default(0);

            $table->date('due_date')->nullable();

            $table->text('waiver_reason')->nullable();

            $table->foreignId('waived_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // One contribution per member per month
            $table->unique(
                ['member_id', 'year', 'month'],
                'monthly_contributions_member_year_month_unique'
            );

            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_contributions');
    }
};
