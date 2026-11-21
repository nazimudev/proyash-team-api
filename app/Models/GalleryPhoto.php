<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'image_path',
        'activity_date',
        'alt_text',
        'status',
    ];

    protected $casts = [
        'activity_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url(
            $this->image_path
        );
    }
}
