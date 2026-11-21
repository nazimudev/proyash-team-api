<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Access is enforced by auth:sanctum + admin middleware on the routes.
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number may only contain digits, spaces, +, - and parentheses.',
            'image.max'   => 'The image must not be larger than 2 MB.',
            'image.mimes' => 'The image must be a JPG, PNG or WebP file.',
        ];
    }
}
