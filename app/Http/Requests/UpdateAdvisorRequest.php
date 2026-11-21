<?php

namespace App\Http\Requests;

class UpdateAdvisorRequest extends StoreAdvisorRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'designation'  => ['sometimes', 'required', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }
}
