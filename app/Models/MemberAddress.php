<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAddress extends Model
{
    protected $fillable = [
        'member_id',
        'type',
        'village',
        'post_office',
        'upazila',
        'district',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
