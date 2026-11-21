<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    // Trim and strip HTML tags before validating.
    protected function prepareForValidation(): void
    {
        $clean = fn($v) => is_string($v) ? trim(strip_tags($v)) : $v;

        $this->merge([
            'name'    => $clean($this->input('name')),
            'phone'   => $clean($this->input('phone')) ?: null,
            'subject' => $clean($this->input('subject')),
            'message' => $clean($this->input('message')),
            'email'   => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'], // honeypot, real users leave it empty
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Use digits, spaces, +, - and parentheses only.',
        ];
    }
}
