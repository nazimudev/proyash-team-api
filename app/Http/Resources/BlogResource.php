<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Blog */
class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            // The admin list never shows the full text, so it is left out there to keep the response small.
            'content' => $this->when(! $request->routeIs('*blogs.index'), $this->content),
            'featured_image' => $this->featured_image, // storage-relative path, e.g. "blogs/example.jpg"
            'category' => $this->whenLoaded('category', fn() => new BlogCategoryResource($this->category)),
            'author_name' => $this->author_name,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
