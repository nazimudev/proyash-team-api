<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = ['phone', 'email', 'working_hours', 'map_embed_url'];

    /** The one and only settings row (created empty on first access). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }
}
