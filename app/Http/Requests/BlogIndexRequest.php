<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class BlogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin-only check comes with the route middleware (Step 7). Anonymous requests are always rejected here.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(Blog::STATUSES)],
            'category_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }

    /** Only the keys Blog::scopeFilter() understands. */
    public function filters(): array
    {
        return Arr::only($this->validated(), ['search', 'status', 'category_id']);
    }
}
