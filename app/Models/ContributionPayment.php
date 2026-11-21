<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionPayment extends Model
{
    protected $fillable = [
        'contribution_id',
        'member_id',
        'payment_method_id',
        'amount',
        'transaction_id',
        'phone_number',
        'receipt_path',
        'payment_date',
        'status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(MonthlyContribution::class, 'contribution_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ContributionPaymentLog::class, 'payment_id');
    }
}
