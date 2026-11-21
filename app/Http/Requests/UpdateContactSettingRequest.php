<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced by auth:sanctum + admin middleware
    }

    protected function prepareForValidation(): void
    {
        $clean = fn($v) => is_string($v) ? (trim(strip_tags($v)) ?: null) : $v;

        $map = $this->input('map_embed_url');
        if (is_string($map)) {
            $map = trim($map);
            // Admin may paste the whole <iframe ...> snippet from Google Maps; keep only its src.
            if (preg_match('/src=["\']([^"\']+)["\']/i', $map, $m)) {
                $map = html_entity_decode($m[1]);
            }
            $map = $map ?: null;
        }

        $this->merge([
            'phone'         => $clean($this->input('phone')),
            'email'         => $clean($this->input('email')),
            'working_hours' => $clean($this->input('working_hours')),
            'map_embed_url' => $map,
        ]);
    }

    public function rules(): array
    {
        return [
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email'         => ['nullable', 'email', 'max:255'],
            'working_hours' => ['nullable', 'string', 'max:500'],
            'map_embed_url' => [
                'nullable',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $u = parse_url($value);
                    $ok = $u
                        && ($u['scheme'] ?? '') === 'https'
                        && in_array($u['host'] ?? '', ['www.google.com', 'google.com'], true)
                        && str_starts_with($u['path'] ?? '', '/maps/embed');
                    if (! $ok) {
                        $fail('Use a Google Maps embed link (Share → Embed a map → copy the src URL).');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Use digits, spaces, +, - and parentheses only.'];
    }
}
