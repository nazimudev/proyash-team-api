<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],

            'expense_category_id' => [
                'required',
                'exists:expense_categories,id',
            ],

            'expense_type' => [
                'required',
                'in:project,general',
            ],

            'project_id' => [
                'nullable',
                'required_if:expense_type,project',
                'prohibited_if:expense_type,general',
                'exists:projects,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'expense_date' => ['required', 'date'],

            'payment_method' => [
                'required',
                'in:cash,bkash,nagad,rocket,bank_transfer,cheque,other',
            ],

            'paid_to' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'receipt' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
        ];
    }
}
