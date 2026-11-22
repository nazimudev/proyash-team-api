<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class MemberController extends Controller
{
    public function update(Request $request, $id)
    {
        $member = Member::with(['user', 'addresses'])->findOrFail($id);

        $validated = $request->validate([
            // User information
            'full_name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($member->user_id),
            ],

            // Member information
            'full_name_bn' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'marital_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'educational_qualification' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blood_group' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'A+',
                    'A-',
                    'B+',
                    'B-',
                    'O+',
                    'O-',
                    'AB+',
                    'AB-',
                ]),
            ],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:30'],

            // Profile photo
            'profile_photo' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            // Addresses
            'addresses' => ['sometimes', 'array'],
            'addresses.permanent' => ['sometimes', 'array'],
            'addresses.permanent.village' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.permanent.post_office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.permanent.upazila' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.permanent.district' => ['sometimes', 'nullable', 'string', 'max:255'],

            'addresses.present' => ['sometimes', 'array'],
            'addresses.present.village' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.present.post_office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.present.upazila' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.present.district' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $newPhotoPath = null;
        $oldPhotoPath = $member->profile_photo_path;

        try {
            if ($request->hasFile('profile_photo')) {
                $newPhotoPath = $request->file('profile_photo')
                    ->store('members/profiles', 'public');
            }

            DB::transaction(function () use (
                $member,
                $validated,
                $newPhotoPath
            ) {
                // Update user table
                if ($member->user) {
                    if (array_key_exists('full_name_en', $validated)) {
                        $member->user->name = $validated['full_name_en'];
                    }

                    if (array_key_exists('email', $validated)) {
                        $member->user->email = $validated['email'];
                    }

                    $member->user->save();
                }

                // Update members table
                $memberFields = [
                    'full_name_bn',
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
                    'mobile_number',
                ];

                $memberData = [];

                foreach ($memberFields as $field) {
                    if (array_key_exists($field, $validated)) {
                        $memberData[$field] = $validated[$field];
                    }
                }

                if ($newPhotoPath) {
                    $memberData['profile_photo_path'] = $newPhotoPath;
                }

                if (!empty($memberData)) {
                    $member->update($memberData);
                }

                // Update addresses without deleting existing records
                foreach (['permanent', 'present'] as $type) {
                    $addressData = $validated['addresses'][$type] ?? null;

                    if ($addressData !== null) {
                        $member->addresses()->updateOrCreate(
                            ['type' => $type],
                            $addressData
                        );
                    }
                }
            });

            // Delete old photo only after successful database update
            if (
                $newPhotoPath &&
                $oldPhotoPath &&
                $oldPhotoPath !== $newPhotoPath
            ) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member information updated successfully.',
                'data' => $member->fresh()->load([
                    'user',
                    'addresses',
                    'agreements',
                ]),
            ]);
        } catch (Throwable $e) {
            // Clean up newly uploaded file if update fails
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update member information.',
            ], 500);
        }
    }

    // Get Member
    public function profile(Request $request)
    {
        $user = $request->user();

        $member = Member::with([
            'user',
            'addresses',
        ])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $addresses = $member->addresses
            ->keyBy('type');

        return response()->json([
            'success' => true,
            'message' => 'Member profile retrieved successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],

                'member' => $member,

                'addresses' => [
                    'permanent' => $addresses->get('permanent'),
                    'present' => $addresses->get('present'),
                ],
            ],
        ]);
    }


    public function account(Request $request)
    {
        $user = $request->user();

        $member = Member::with([
            'user',
            'addresses',
        ])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $addresses = $member->addresses->keyBy('type');

        $memberData = $member->toArray();

        // Avoid duplicating related data.
        unset($memberData['user'], $memberData['addresses']);

        // Public profile photo URL.
        $memberData['profile_photo_url'] = $member->profile_photo_path
            ? Storage::disk('public')->url($member->profile_photo_path)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Member account retrieved successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],

                'member' => $memberData,

                'addresses' => [
                    'permanent' => $addresses->get('permanent'),
                    'present' => $addresses->get('present'),
                ],

                'membership' => [
                    'member_status' => $member->status,
                ],
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $member = Member::with(['user', 'addresses'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'full_name_en' => ['required', 'string', 'max:255'],
            'full_name_bn' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'educational_qualification' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'mobile_number' => ['nullable', 'string', 'max:30'],

            'addresses' => ['nullable', 'array'],
            'addresses.permanent' => ['nullable', 'array'],
            'addresses.present' => ['nullable', 'array'],

            'addresses.permanent.village' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.post_office' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.upazila' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.district' => ['nullable', 'string', 'max:255'],

            'addresses.present.village' => ['nullable', 'string', 'max:255'],
            'addresses.present.post_office' => ['nullable', 'string', 'max:255'],
            'addresses.present.upazila' => ['nullable', 'string', 'max:255'],
            'addresses.present.district' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $user, $member) {

            $user->update([
                'name' => $validated['full_name_en'],
                'email' => $validated['email'],
            ]);

            $member->update([
                'full_name_bn' => $validated['full_name_bn'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'educational_qualification' => $validated['educational_qualification'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'mobile_number' => $validated['mobile_number'] ?? null,
            ]);

            foreach (['permanent', 'present'] as $type) {
                if (isset($validated['addresses'][$type])) {
                    $member->addresses()->updateOrCreate(
                        ['type' => $type],
                        $validated['addresses'][$type]
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Member profile updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->fresh()->name,
                    'email' => $user->fresh()->email,
                ],
                'member' => $member->fresh(),
                'addresses' => $member->addresses()->get()->keyBy('type'),
            ],
        ]);
    }


    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $user = $request->user();

        $member = Member::where('user_id', $user->id)
            ->firstOrFail();

        $oldPhotoPath = $member->profile_photo_path;

        $newPhotoPath = $request->file('profile_photo')
            ->store('members/profiles', 'public');

        try {
            $member->update([
                'profile_photo_path' => $newPhotoPath,
            ]);

            if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully.',
                'data' => [
                    'profile_photo_url' => Storage::disk('public')
                        ->url($newPhotoPath),
                ],
            ]);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($newPhotoPath);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo.',
            ], 500);
        }
    }
}
