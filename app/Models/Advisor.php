<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Advisor extends Model
{
    protected $fillable = ['name', 'designation', 'image', 'address', 'phone'];

    // Adds "image_url" to every JSON response.
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
