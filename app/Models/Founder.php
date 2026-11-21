<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Founder extends Model
{
    protected $fillable = [
        'full_name_en',
        'full_name_bn',
        'designation',
        'photo',
        'biography',
        'education',
        'professional_experience',
        'community_involvement',
        'message_heading',
        'message',
        'status',
        'display_order',
        'confirmed',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'confirmed' => 'boolean',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : null;
    }
}
