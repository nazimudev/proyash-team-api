<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'project_name' => [
                'required',
                'string',
                'max:255',
            ],

            'project_id' => [
                'nullable',
                'string',
                'max:100',
                'unique:projects,project_id',
            ],

            'project_category_id' => [
                'required',
                'integer',
                'exists:project_categories,id',
            ],

            'description' => [
                'required',
                'string',
            ],

            // Cover Image
            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],

            // Multiple Gallery Images
            'images' => [
                'nullable',
                'array',
                'max:20',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],

            // Location & Responsibility
            'location' => [
                'required',
                'string',
                'max:255',
            ],

            'responsible_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'contact_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            // Timeline
            'start_date' => [
                'required',
                'date',
            ],

            'expected_end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            // Budget & Funding
            'allocated_budget' => [
                'required',
                'numeric',
                'min:0',
            ],

            'funding_source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'funding_details' => [
                'nullable',
                'string',
            ],

            // Status & Progress
            'status' => [
                'required',
                'in:planned,in_progress,complete,on_hold,cancelled',
            ],

            'progress' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            // Project Activities
            'activities' => [
                'nullable',
                'array',
                'max:50',
            ],

            'activities.*.title' => [
                'required',
                'string',
                'max:255',
            ],

            'activities.*.description' => [
                'nullable',
                'string',
            ],

            'activities.*.activity_date' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'project_name.required' => 'Project name is required.',

            'project_category_id.required' => 'Please select a project category.',
            'project_category_id.exists' => 'Selected project category is invalid.',

            'description.required' => 'Project description is required.',

            'cover_image.image' => 'Cover image must be a valid image.',
            'cover_image.max' => 'Cover image must not exceed 5MB.',

            'images.array' => 'Gallery images must be submitted as a valid list.',
            'images.max' => 'You can upload a maximum of 20 gallery images.',
            'images.*.image' => 'Each gallery file must be a valid image.',
            'images.*.max' => 'Each gallery image must not exceed 5MB.',

            'location.required' => 'Project location is required.',

            'start_date.required' => 'Project start date is required.',
            'expected_end_date.required' => 'Expected end date is required.',
            'expected_end_date.after_or_equal' => 'Expected end date must be after or equal to start date.',

            'allocated_budget.required' => 'Allocated budget is required.',
            'allocated_budget.numeric' => 'Allocated budget must be a valid number.',

            'status.required' => 'Please select a project status.',
            'status.in' => 'Selected project status is invalid.',

            'progress.required' => 'Project progress is required.',
            'progress.integer' => 'Project progress must be a whole number.',
            'progress.min' => 'Project progress cannot be less than 0.',
            'progress.max' => 'Project progress cannot exceed 100.',

            'activities.array' => 'Activities must be submitted as a valid list.',
            'activities.max' => 'You can add a maximum of 50 activities.',
            'activities.*.title.required' => 'Every activity must have a title.',
        ];
    }
}
