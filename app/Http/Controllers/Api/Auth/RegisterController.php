<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegisterController extends Controller
{
    /**
     * Register a new Proyash member.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Get validated data
        $data = $request->validated();

        // Upload profile photo
        $photoPath = null;

        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')
                ->store('members/profiles', 'public');
        }

        try {

            // Start Database Transaction
            $user = DB::transaction(function () use ($data, $photoPath) {

                /*
                |--------------------------------------------------------------------------
                | 1. Create User Account
                |--------------------------------------------------------------------------
                */

                $user = new User();

                $user->name = $data['full_name_en'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);
                $user->role = 'member';
                $user->is_active = false;

                $user->save();

                /*
                |--------------------------------------------------------------------------
                | 2. Create Member Profile
                |--------------------------------------------------------------------------
                */

                $member = $user->member()->create([

                    'full_name_bn' => $data['full_name_bn'] ?? null,

                    'father_name' => $data['father_name'],
                    'mother_name' => $data['mother_name'],

                    'date_of_birth' => $data['date_of_birth'],
                    'nationality' => $data['nationality'],
                    'religion' => $data['religion'] ?? null,

                    'occupation' => $data['occupation'],
                    'gender' => $data['gender'] ?? null,

                    'marital_status' => $data['marital_status'],
                    'educational_qualification' =>
                    $data['educational_qualification'],

                    'blood_group' => $data['blood_group'] ?? null,
                    'mobile_number' => $data['mobile_number'],

                    'profile_photo_path' => $photoPath,

                    'status' => 'pending',

                ]);

                /*
                |--------------------------------------------------------------------------
                | 3. Create Permanent & Present Addresses
                |--------------------------------------------------------------------------
                */

                $member->addresses()->createMany([

                    [
                        'type' => 'permanent',

                        'village' => $data['permanent_village'],
                        'post_office' => $data['permanent_post_office'],
                        'upazila' => $data['permanent_upazila'],
                        'district' => $data['permanent_district'],
                    ],

                    [
                        'type' => 'present',

                        'village' => $data['present_village'],
                        'post_office' => $data['present_post_office'],
                        'upazila' => $data['present_upazila'],
                        'district' => $data['present_district'],
                    ],

                ]);

                /*
                |--------------------------------------------------------------------------
                | 4. Save Membership Agreements
                |--------------------------------------------------------------------------
                */

                $agreements = [
                    'membership_conditions',
                    'applicant_declaration',
                    'privacy_policy',
                    'terms_of_service',
                ];

                $member->agreements()->createMany(

                    array_map(function ($agreement) {

                        return [
                            'agreement_type' => $agreement,
                            'version' => 'v1.0',
                            'accepted_at' => now(),
                        ];
                    }, $agreements)

                );

                return $user;
            });

            /*
            |--------------------------------------------------------------------------
            | 5. Registration Success Response
            |--------------------------------------------------------------------------
            */

            $user->load([
                'member.addresses',
                'member.agreements',
            ]);

            return response()->json([

                'success' => true,

                'message' =>
                'Your membership application has been submitted successfully. Please wait for admin approval.',

                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                    ],

                    'member' => $user->member,
                ],

            ], 201);
        } catch (Throwable $exception) {

            // Remove uploaded photo if database transaction fails
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }
    }
}
