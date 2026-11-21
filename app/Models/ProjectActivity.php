<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectActivity extends Model
{
    //
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'activity_date',
        'sort_order',
    ];


    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
