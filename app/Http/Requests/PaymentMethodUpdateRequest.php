<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $updating = $this->isMethod('PATCH')
            || $this->isMethod('PUT');

        $required = $updating ? 'sometimes' : 'required';

        return [
            'name' => [
                $required,
                'string',
                'max:100',
            ],

            'type' => [
                $required,
                Rule::in(['mobile', 'bank', 'cash', 'other']),
            ],

            'account_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'account_holder' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'description' => [
                $required,
                'string',
            ],

            'steps' => [
                'sometimes',
                'nullable',
                'array',
                'max:20',
            ],

            'steps.*' => [
                'array:key,text',
            ],

            'steps.*.key' => [
                'required',
                'string',
                'max:100',
            ],

            'steps.*.text' => [
                'required',
                'string',
                'max:500',
            ],

            'requires_transaction_id' => [
                'sometimes',
                'boolean',
            ],

            'requires_phone' => [
                'sometimes',
                'boolean',
            ],

            'requires_payment_proof' => [
                'sometimes',
                'boolean',
            ],

            'display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
