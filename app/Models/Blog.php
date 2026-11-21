<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category_id',
        'author_name',
        'status',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Admin list filters: title search, status, category.
     * Expects keys: search, status, category_id (all optional).
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(filled($filters['search'] ?? null), function (Builder $q) use ($filters) {
                // Escape LIKE wildcards so "%" and "_" are searched literally.
                $term = addcslashes((string) $filters['search'], '\\%_');
                $q->where('title', 'like', "%{$term}%");
            })
            ->when(filled($filters['status'] ?? null), fn(Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['category_id'] ?? null), fn(Builder $q) => $q->where('category_id', $filters['category_id']));
    }

    /**
     * For the future public website: published posts whose publish time has arrived.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now());
    }
}
