<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id',
        'full_name_bn',
        'father_name',
        'mother_name',
        'date_of_birth',
        'nationality',
        'religion',
        'occupation',
        'gender',
        'marital_status',
        'educational_qualification',
        'blood_group',
        'mobile_number',
        'profile_photo_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    // Member belongs to a User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Member has multiple addresses
    public function addresses(): HasMany
    {
        return $this->hasMany(MemberAddress::class);
    }

    // Member has multiple agreements
    public function agreements(): HasMany
    {
        return $this->hasMany(MemberAgreement::class);
    }

    // Admin who reviewed this member
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function contributionSettings(): HasMany
    {
        return $this->hasMany(MemberContributionSetting::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(MonthlyContribution::class);
    }

    public function contributionPayments(): HasMany
    {
        return $this->hasMany(ContributionPayment::class);
    }
}
