<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Services\Member\MemberDashboardService;
use Illuminate\Http\Request;

class MemberDashboardController extends Controller
{
    public function index(
        Request $request,
        MemberDashboardService $dashboardService
    ) {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found.',
            ], 403);
        }

        $dashboard = $dashboardService->getDashboard($member);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}
