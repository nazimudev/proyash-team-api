<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberContributionSetting extends Model
{
    protected $fillable = [
        'member_id',
        'monthly_amount',
        'effective_from',
        'effective_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
