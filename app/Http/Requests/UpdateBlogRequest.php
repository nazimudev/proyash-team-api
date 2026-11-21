<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends StoreBlogRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_featured_image' => ['sometimes', 'boolean'],
        ]);
    }

    /** Same slug rules, but the post being edited may keep its own slug. */
    protected function slugRules(): array
    {
        $blog = $this->route('blog');
        $id = $blog instanceof Blog ? $blog->getKey() : $blog;

        return [...$this->slugFormatRules(), Rule::unique('blogs', 'slug')->ignore($id)];
    }
}
