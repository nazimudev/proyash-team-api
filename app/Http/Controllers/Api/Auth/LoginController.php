<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Login API
     */
    public function login(Request $request): JsonResponse
    {
        // Validate login credentials
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        // Verify email and password
        if (
            ! $user ||
            ! Hash::check($validated['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check account status
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive or awaiting approval.',
            ], 403);
        }

        // Allow only supported roles
        if (! in_array($user->role, ['admin', 'member'], true)) {
            return response()->json([
                'message' => 'Your account role is not authorized.',
            ], 403);
        }

        $user->load('member');

        // Create Sanctum API token
        $token = $user->createToken('proyash-api')->plainTextToken;

        // Return user details and token

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,

                'member' => $user->member ? [
                    'id' => $user->member->id,
                    'full_name_bn' => $user->member->full_name_bn,
                    'full_name_en' => $user->member->full_name_en,
                    'father_name' => $user->member->father_name,
                    'mother_name' => $user->member->mother_name,
                    'date_of_birth' => $user->member->date_of_birth,
                    'nationality' => $user->member->nationality,
                    'religion' => $user->member->religion,
                    'occupation' => $user->member->occupation,
                    'gender' => $user->member->gender,
                    'marital_status' => $user->member->marital_status,
                    'educational_qualification' => $user->member->educational_qualification,
                    'blood_group' => $user->member->blood_group,
                    'mobile' => $user->member->mobile,
                    'profile_photo' => $user->member->profile_photo,

                    'permanent_village' => $user->member->permanent_village,
                    'permanent_post_office' => $user->member->permanent_post_office,
                    'permanent_upazila' => $user->member->permanent_upazila,
                    'permanent_district' => $user->member->permanent_district,

                    'present_village' => $user->member->present_village,
                    'present_post_office' => $user->member->present_post_office,
                    'present_upazila' => $user->member->present_upazila,
                    'present_district' => $user->member->present_district,

                    'membership_status' => $user->member->membership_status,
                ] : null,
            ],

            'redirect_to' => $user->role === 'admin'
                ? '/admin'
                : '/member',
        ], 200);
    }

    /**
     * Logout API
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ], 200);
    }
}
