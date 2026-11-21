<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Unauthorized.');
    }

    public function show(Request $request)
    {
        $this->ensureAdmin($request);

        $user = $request->user()->load([
            'member.addresses',
        ]);

        $member = $user->member;

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'account' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],

                'member' => $member ? [
                    'id' => $member->id,
                    'full_name_bn' => $member->full_name_bn,
                    'father_name' => $member->father_name,
                    'mother_name' => $member->mother_name,
                    'date_of_birth' => $member->date_of_birth,
                    'nationality' => $member->nationality,
                    'religion' => $member->religion,
                    'occupation' => $member->occupation,
                    'gender' => $member->gender,
                    'marital_status' => $member->marital_status,
                    'educational_qualification' => $member->educational_qualification,
                    'blood_group' => $member->blood_group,
                    'mobile_number' => $member->mobile_number,
                    'profile_photo' => $member->profile_photo_path,
                    'membership_status' => $member->status,

                    'addresses' => $member->addresses->map(fn($address) => [
                        'id' => $address->id,
                        'type' => $address->type,
                        'village' => $address->village,
                        'post_office' => $address->post_office,
                        'upazila' => $address->upazila,
                        'district' => $address->district,
                    ])->values(),
                ] : null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'full_name_bn' => ['sometimes', 'nullable', 'string', 'max:255'],
            'full_name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:150'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'marital_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'educational_qualification' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:30'],

            'permanent_village' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_post_office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_upazila' => ['sometimes', 'nullable', 'string', 'max:255'],
            'permanent_district' => ['sometimes', 'nullable', 'string', 'max:255'],

            'present_village' => ['sometimes', 'nullable', 'string', 'max:255'],
            'present_post_office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'present_upazila' => ['sometimes', 'nullable', 'string', 'max:255'],
            'present_district' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
            $user->save();
        }

        $memberFields = [
            'full_name_bn',
            'full_name_en',
            'father_name',
            'mother_name',
            'date_of_birth',
            'nationality',
            'religion',
            'occupation',
            'gender',
            'marital_status',
            'educational_qualification',
            'blood_group',
            'mobile',
            'permanent_village',
            'permanent_post_office',
            'permanent_upazila',
            'permanent_district',
            'present_village',
            'present_post_office',
            'present_upazila',
            'present_district',
        ];

        $memberData = array_intersect_key(
            $validated,
            array_flip($memberFields)
        );

        if (!empty($memberData)) {
            $member = $user->member;

            abort_if(!$member, 404, 'Member profile not found.');

            // Only explicitly validated fields are written.
            $member->forceFill($memberData)->save();
        }

        return $this->show($request);
    }
}
