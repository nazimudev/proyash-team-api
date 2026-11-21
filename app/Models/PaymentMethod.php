<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'type',
        'account_number',
        'account_holder',
        'description',
        'steps',
        'requires_transaction_id',
        'requires_phone',
        'requires_payment_proof',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'requires_transaction_id' => 'boolean',
            'requires_phone' => 'boolean',
            'requires_payment_proof' => 'boolean',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function contributionPayments(): HasMany
    {
        return $this->hasMany(ContributionPayment::class);
    }
}
