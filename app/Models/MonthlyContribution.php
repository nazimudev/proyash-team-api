<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyContribution extends Model
{
    protected $fillable = [
        'member_id',
        'year',
        'month',
        'required_amount',
        'waived_amount',
        'due_date',
        'waiver_reason',
        'waived_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'required_amount' => 'decimal:2',
            'waived_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            ContributionPayment::class,
            'contribution_id'
        );
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
