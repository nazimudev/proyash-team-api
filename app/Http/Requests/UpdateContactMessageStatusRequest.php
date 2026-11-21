<?php

namespace App\Http\Requests;

use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactMessageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced by auth:sanctum + admin middleware
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
        ];
    }
}
