<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PendingMemberController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        // Only admin can access this API
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this resource.',
            ], 403);
        }

        $members = Member::with([
            'user:id,name,email,is_active,role',
            'addresses:id,member_id,type,village,post_office,upazila,district',
            'agreements:id,member_id,agreement_type,version,accepted_at',
        ])
            // Exclude users whose role is admin
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'admin');
            })
            ->latest()
            ->get();

        $data = $members->map(function ($member) {
            $member->profile_photo_url = $member->profile_photo_path
                ? Storage::disk('public')->url($member->profile_photo_path)
                : null;

            return $member;
        });

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully.',
            'data' => $data,
            'total' => $data->count(),
        ]);
    }


    /**
     * Approve member api
     */

    public function approve(Request $request, Member $member)
    {
        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be approved.',
            ], 422);
        }

        try {

            DB::transaction(function () use ($request, $member) {

                // Approve member application
                $member->update([
                    'status' => 'approved',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                // Activate user account
                $member->user()->update([
                    'is_active' => true,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Member approved successfully. Account is now active.',

                'data' => [
                    'member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'status' => 'approved',
                    'is_active' => true,
                ],

            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve member.',
            ], 500);
        }
    }


    public function list_member(Request $request)
    {
        try {
            $members = Member::with('user')
                ->whereHas('user', function ($query) {
                    $query->where('role', '!=', 'admin');
                })
                ->latest()
                ->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Members retrieved successfully.',
                'data' => $members->items(),
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                    'last_page' => $members->lastPage(),
                    'has_more_pages' => $members->hasMorePages(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve member list.',
            ], 500);
        }
    }


    public function destroy($id)
    {
        $member = Member::with('user')->find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found.',
            ], 404);
        }

        DB::transaction(function () use ($member) {

            // Get related user
            $user = $member->user;

            // Delete member record
            $member->delete();

            // Delete related user
            if ($user) {
                $user->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Member and related user deleted successfully.',
        ], 200);
    }
}
