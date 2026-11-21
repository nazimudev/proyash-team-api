<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Registration validation rules.
     */
    public function rules(): array
    {
        return [

            // Account Information
            'full_name_en' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            // Personal Information
            'full_name_bn' => [
                'nullable',
                'string',
                'max:255',
            ],

            'father_name' => [
                'required',
                'string',
                'max:255',
            ],

            'mother_name' => [
                'required',
                'string',
                'max:255',
            ],

            'date_of_birth' => [
                'required',
                'date',
                'before:today',
            ],

            'nationality' => [
                'required',
                'string',
                'max:100',
            ],

            'religion' => [
                'nullable',
                'string',
                'max:100',
            ],

            'occupation' => [
                'required',
                'string',
                'max:150',
            ],

            'gender' => [
                'nullable',
                'string',
                'max:30',
            ],

            'marital_status' => [
                'required',
                'string',
                'max:50',
            ],

            'educational_qualification' => [
                'required',
                'string',
                'max:150',
            ],

            'blood_group' => [
                'nullable',
                'string',
                'max:5',
            ],

            'mobile_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]+$/',
            ],

            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            // Permanent Address
            'permanent_village' => [
                'required',
                'string',
                'max:150',
            ],

            'permanent_post_office' => [
                'required',
                'string',
                'max:150',
            ],

            'permanent_upazila' => [
                'required',
                'string',
                'max:150',
            ],

            'permanent_district' => [
                'required',
                'string',
                'max:150',
            ],

            // Present Address
            'present_village' => [
                'required',
                'string',
                'max:150',
            ],

            'present_post_office' => [
                'required',
                'string',
                'max:150',
            ],

            'present_upazila' => [
                'required',
                'string',
                'max:150',
            ],

            'present_district' => [
                'required',
                'string',
                'max:150',
            ],

            // Membership Agreements
            'membership_conditions' => [
                'required',
                'accepted',
            ],

            'applicant_declaration' => [
                'required',
                'accepted',
            ],

            'privacy_policy' => [
                'required',
                'accepted',
            ],

            'terms_of_service' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'email.unique' =>
            'This email address is already registered.',

            'password.confirmed' =>
            'Password confirmation does not match.',

            'date_of_birth.before' =>
            'Date of birth must be before today.',

            'mobile_number.regex' =>
            'Please enter a valid mobile number.',

            'profile_photo.image' =>
            'Profile photo must be a valid image.',

            'profile_photo.max' =>
            'Profile photo cannot exceed 2MB.',

            'membership_conditions.accepted' =>
            'You must accept the membership conditions.',

            'applicant_declaration.accepted' =>
            'You must accept the applicant declaration.',

            'privacy_policy.accepted' =>
            'You must accept the Privacy Policy.',

            'terms_of_service.accepted' =>
            'You must accept the Terms of Service.',
        ];
    }
}
