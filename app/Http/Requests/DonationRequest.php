<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationRequest extends FormRequest
{
    /**
     * Anyone can submit a donation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare request data.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'donor_type' => $this->input('donor_type', 'named'),
        ]);
    }

    /**
     * Donation validation rules.
     */
    public function rules(): array
    {
        return [
            'donor_type' => [
                'required',
                Rule::in(['named', 'anonymous']),
            ],

            'full_name' => [
                'nullable',
                'required_if:donor_type,named',
                'string',
                'max:150',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:9999999999.99',
            ],

            'purpose' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')
                    ->where('is_active', true),
            ],

            'transaction_id' => [
                'required',
                'string',
                'max:150',
                Rule::unique('donations', 'transaction_id')
                    ->where(
                        'payment_method_id',
                        $this->input('payment_method_id')
                    ),
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^(\+?88)?01[3-9]\d{8}$/',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'message' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'donor_type.required' => 'Donor type is required.',
            'donor_type.in' => 'Invalid donor type.',

            'full_name.required_if' =>
            'Full name is required for named donations.',

            'amount.required' => 'Donation amount is required.',
            'amount.numeric' => 'Please enter a valid amount.',
            'amount.gt' => 'Donation amount must be greater than zero.',
            'amount.decimal' => 'Amount can have up to 2 decimal places.',

            'payment_method_id.required' =>
            'Please select a payment method.',
            'payment_method_id.exists' =>
            'The selected payment method is unavailable.',

            'transaction_id.required' =>
            'Transaction ID is required.',
            'transaction_id.unique' =>
            'This transaction ID has already been submitted for this payment method.',

            'phone.required' => 'Phone number is required.',
            'phone.regex' =>
            'Please enter a valid Bangladeshi phone number.',

            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
