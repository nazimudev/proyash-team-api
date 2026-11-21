<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'donor_type',
        'full_name',
        'amount',
        'donation_date',
        'purpose',
        'payment_method_id',
        'transaction_id',
        'phone',
        'email',
        'message',
        'rejection_reason',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method_id' => 'integer',
            'donation_date' => 'date:Y-m-d',
            'verified_at' => 'datetime',
        ];
    }


    // Donation belongs to a payment method
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Admin who verified the donation
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Pending donations
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Approved donations
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Rejected donations
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Named donations
    public function scopeNamed($query)
    {
        return $query->where('donor_type', 'named');
    }

    // Anonymous donations
    public function scopeAnonymous($query)
    {
        return $query->where('donor_type', 'anonymous');
    }
}
