<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') ||
            $this->isMethod('PATCH');

        return [
            'title' => [
                'required',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'category' => [
                'required',
                'string',
                Rule::in(config('gallery.categories')),
            ],

            'image' => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('gallery.image.max_size'),
            ],

            'activity_date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:200',
            ],

            'status' => [
                'required',
                Rule::in(config('gallery.statuses')),
            ],
        ];
    }
}
