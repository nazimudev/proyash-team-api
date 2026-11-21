<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        // Basic Information
        'name',
        'project_id',
        'category_id',
        'description',

        // Media
        'cover_image',

        // Location & Responsibility
        'location',
        'responsible_person',
        'contact_phone',

        // Timeline
        'start_date',
        'expected_end_date',

        // Budget & Funding
        'allocated_budget',
        'funding_source',
        'funding_details',

        // Status & Progress
        'status',
        'progress',

        // Internal Notes
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'allocated_budget' => 'decimal:2',
            'progress' => 'integer',
        ];
    }

    /**
     * A project belongs to a category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(ProjectImage::class)
            ->orderBy('sort_order');
    }

    public function activities()
    {
        return $this->hasMany(ProjectActivity::class)
            ->orderBy('sort_order');
    }
}
