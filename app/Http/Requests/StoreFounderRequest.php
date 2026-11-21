<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFounderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name_en' => ['required', 'string', 'max:255'],
            'full_name_bn' => ['nullable', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'biography' => ['nullable', 'string'],
            'education' => ['nullable', 'string'],
            'professional_experience' => ['nullable', 'string'],
            'community_involvement' => ['nullable', 'string'],

            'message_heading' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],

            'status' => [
                'required',
                Rule::in(['draft', 'published']),
            ],

            'display_order' => [
                'required',
                'integer',
                'min:1',
                'unique:founders,display_order',
            ],

            'confirmed' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $this->input('status') === 'published'
                    && !$this->boolean('confirmed')
                ) {
                    $validator->errors()->add(
                        'confirmed',
                        'You must confirm the founder content before publishing.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'full_name_en.required' => 'English name is required.',
            'designation.required' => 'Designation is required.',
            'photo.image' => 'Please upload a valid image.',
            'photo.mimes' => 'Only JPG, JPEG, PNG and WebP images are allowed.',
            'photo.max' => 'Photo size cannot exceed 5 MB.',
            'display_order.unique' => 'This display order is already taken.',
            'display_order.min' => 'Display order must be at least 1.',
        ];
    }
}
