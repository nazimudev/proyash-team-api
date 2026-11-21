<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin-only check comes with the route middleware (Step 7). Anonymous requests are always rejected here.
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('slug'))) {
            $this->merge(['slug' => mb_strtolower(trim($this->input('slug')))]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => $this->slugRules(),
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:50000'],
            'category_id' => ['required', 'integer', 'exists:blog_categories,id'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(Blog::STATUSES)],
            'published_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_featured' => ['sometimes', 'boolean'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function slugFormatRules(): array
    {
        return [
            'required',
            'string',
            'max:200',
            'regex:/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/u',
        ];
    }

    protected function slugRules(): array
    {
        return [...$this->slugFormatRules(), Rule::unique('blogs', 'slug')];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may contain letters, numbers, and single hyphens only.',
            'slug.unique' => 'This slug is already used by another post.',
            'featured_image.image' => 'The featured image must be an image.',
            'featured_image.mimes' => 'The featured image must be a JPG, PNG, or WebP file.',
            'featured_image.max' => 'The featured image must not be larger than 5 MB.',
            'featured_image.uploaded' => 'The featured image could not be uploaded. It may be larger than the server allows.',
            'published_at.date_format' => 'Enter the publish date and time in a valid format.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'author_name' => 'author name',
            'published_at' => 'publish date',
            'featured_image' => 'featured image',
        ];
    }

    /**
     * Post columns only (no file, no flags). The frontend leaves out empty optional fields,
     * so an absent author_name / published_at becomes null.
     */
    public function blogData(): array
    {
        return array_merge(
            ['author_name' => null, 'published_at' => null],
            Arr::except($this->validated(), ['featured_image', 'remove_featured_image']),
            ['is_featured' => $this->boolean('is_featured')],
        );
    }
}
