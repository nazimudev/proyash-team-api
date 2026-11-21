<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionPaymentLog extends Model
{
    protected $fillable = [
        'payment_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            ContributionPayment::class,
            'payment_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
