<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFounderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $founderId = $this->route('id');

        return [
            'full_name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'full_name_bn' => ['nullable', 'string', 'max:255'],
            'designation' => ['sometimes', 'required', 'string', 'max:255'],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_photo' => ['sometimes', 'boolean'],

            'biography' => ['nullable', 'string'],
            'education' => ['nullable', 'string'],
            'professional_experience' => ['nullable', 'string'],
            'community_involvement' => ['nullable', 'string'],

            'message_heading' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],

            'status' => [
                'sometimes',
                Rule::in(['draft', 'published']),
            ],

            'display_order' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('founders', 'display_order')->ignore($founderId),
            ],

            'confirmed' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $founder = \App\Models\Founder::find($this->route('id'));

                $status = $this->input(
                    'status',
                    $founder?->status
                );

                $confirmed = $this->has('confirmed')
                    ? $this->boolean('confirmed')
                    : (bool) $founder?->confirmed;

                if ($status === 'published' && !$confirmed) {
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
            'display_order.unique' => 'This display order is already taken.',
            'photo.max' => 'Photo size cannot exceed 5 MB.',
        ];
    }
}
