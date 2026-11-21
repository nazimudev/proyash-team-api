<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    private const TYPES = ['mobile', 'bank', 'cash', 'other'];

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $mapping = [
            'accountNumber' => 'account_number',
            'accountHolder' => 'account_holder',
            'requiresTransactionId' => 'requires_transaction_id',
            'requiresPhone' => 'requires_phone',
            'requiresPaymentProof' => 'requires_payment_proof',
            'displayOrder' => 'display_order',
            'isActive' => 'is_active',
        ];

        $clean = [];

        foreach ($mapping as $frontend => $database) {
            if ($this->exists($frontend) && !$this->exists($database)) {
                $clean[$database] = $this->input($frontend);
            }
        }

        foreach (['name', 'type', 'account_number', 'account_holder', 'description'] as $field) {
            $value = $clean[$field] ?? $this->input($field);

            if (is_string($value)) {
                $value = trim($value);

                if (
                    $value === '' &&
                    in_array($field, ['account_number', 'account_holder'], true)
                ) {
                    $value = null;
                }

                $clean[$field] = $value;
            }
        }

        if (is_array($this->input('steps'))) {
            $steps = [];

            foreach ($this->input('steps') as $step) {
                if (!is_array($step)) {
                    $steps[] = $step;
                    continue;
                }

                $text = $step['text'] ?? null;

                if (is_string($text)) {
                    $text = trim($text);
                }

                if ($text === '' || $text === null) {
                    continue;
                }

                $steps[] = [
                    'key' => $step['key'] ?? null,
                    'text' => $text,
                ];
            }

            $clean['steps'] = $steps;
        }

        $this->merge($clean);
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST')
            ? 'required'
            : 'sometimes';

        $paymentMethod = $this->route('paymentMethod');

        $type = $this->input('type');

        if (!$type && $paymentMethod instanceof \App\Models\PaymentMethod) {
            $type = $paymentMethod->type;
        }

        $needsAccount = in_array($type, ['mobile', 'bank'], true);
        $needsHolder = $type === 'bank';

        return [
            'name' => [
                $required,
                'string',
                'max:100',
            ],

            'type' => [
                $required,
                'string',
                Rule::in(self::TYPES),
            ],

            'account_number' => [
                Rule::requiredIf($needsAccount),
                'nullable',
                'string',
                'max:100',
            ],

            'account_holder' => [
                Rule::requiredIf($needsHolder),
                'nullable',
                'string',
                'max:150',
            ],

            'description' => [
                $required,
                'string',
                'max:5000',
            ],

            'steps' => [
                'nullable',
                'array',
                'max:20',
            ],

            'steps.*' => [
                'required',
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
                'max:4294967295',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'account_number' => 'account number',
            'account_holder' => 'account holder',
            'requires_transaction_id' => 'transaction ID requirement',
            'requires_phone' => 'phone requirement',
            'requires_payment_proof' => 'payment proof requirement',
            'display_order' => 'display order',
            'steps.*.key' => 'step key',
            'steps.*.text' => 'step instruction',
        ];
    }
}
