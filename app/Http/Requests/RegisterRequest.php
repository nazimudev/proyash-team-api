<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            // Personal information
            'full_name_bn' => ['nullable', 'string', 'max:255'],
            'full_name_en' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'occupation' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['required', 'string', 'max:50'],
            'educational_qualification' => ['required', 'string', 'max:255'],
            'blood_group' => [
                'nullable',
                'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            ],
            'mobile' => ['required', 'string', 'max:30'],

            // Permanent address
            'permanent_village' => ['required', 'string', 'max:255'],
            'permanent_post_office' => ['required', 'string', 'max:255'],
            'permanent_upazila' => ['required', 'string', 'max:255'],
            'permanent_district' => ['required', 'string', 'max:255'],

            // Present address
            'present_village' => ['required', 'string', 'max:255'],
            'present_post_office' => ['required', 'string', 'max:255'],
            'present_upazila' => ['required', 'string', 'max:255'],
            'present_district' => ['required', 'string', 'max:255'],

            // Account credentials
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ];
    }
}
