<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContributionPayment;
use App\Models\Donation;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $year = $validated['year'] ?? now()->year;

        /*
        |--------------------------------------------------------------------------
        | Contribution Summary
        |--------------------------------------------------------------------------
        */

        // Aggregate payment amounts in the database first.
        $paymentAggregates = DB::table('contribution_payments')
            ->select('contribution_id')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN status = 'approved'
                        THEN amount ELSE 0
                    END
                ) as paid_amount
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN status = 'pending'
                        THEN amount ELSE 0
                    END
                ) as pending_amount
            ")
            ->groupBy('contribution_id');

        // Calculate yearly contribution summary.
        $contributionSummary = DB::table('monthly_contributions as mc')
            ->leftJoinSub(
                $paymentAggregates,
                'payments',
                function ($join) {
                    $join->on(
                        'payments.contribution_id',
                        '=',
                        'mc.id'
                    );
                }
            )
            ->where('mc.year', $year)
            ->selectRaw("
                COUNT(mc.id) as total_records,

                COALESCE(
                    SUM(mc.required_amount), 0
                ) as total_required,

                COALESCE(
                    SUM(mc.waived_amount), 0
                ) as total_waived,

                COALESCE(
                    SUM(payments.paid_amount), 0
                ) as total_paid,

                COALESCE(
                    SUM(payments.pending_amount), 0
                ) as total_pending,

                COALESCE(
                    SUM(
                        GREATEST(
                            mc.required_amount
                            - mc.waived_amount
                            - COALESCE(payments.paid_amount, 0),
                            0
                        )
                    ),
                    0
                ) as total_due
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Monthly Contribution Chart
        |--------------------------------------------------------------------------
        */

        $monthlyRows = DB::table('monthly_contributions as mc')
            ->leftJoin(
                'contribution_payments as cp',
                'mc.id',
                '=',
                'cp.contribution_id'
            )
            ->where('mc.year', $year)
            ->select('mc.month')
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN cp.status = 'approved'
                            THEN cp.amount ELSE 0
                        END
                    ),
                    0
                ) as approved
            ")
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN cp.status = 'pending'
                            THEN cp.amount ELSE 0
                        END
                    ),
                    0
                ) as pending
            ")
            ->groupBy('mc.month')
            ->orderBy('mc.month')
            ->get()
            ->keyBy('month');

        // Always return all 12 months.
        $monthlyContributions = collect(range(1, 12))
            ->map(function ($month) use ($monthlyRows) {

                $row = $monthlyRows->get($month);

                return [
                    'month' => $month,

                    'month_name' => date(
                        'F',
                        mktime(0, 0, 0, $month, 1)
                    ),

                    'approved' => round(
                        (float) ($row->approved ?? 0),
                        2
                    ),

                    'pending' => round(
                        (float) ($row->pending ?? 0),
                        2
                    ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Donation Summary
        |--------------------------------------------------------------------------
        */

        $totalDonations = Donation::approved()
            ->sum('amount');

        $pendingDonations = Donation::pending()
            ->sum('amount');

        $donationCount = Donation::approved()
            ->count();

        $pendingDonationCount = Donation::pending()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Expense Summary
        |--------------------------------------------------------------------------
        */

        // All-time expense total, matching the previous controller.
        $totalExpenses = Expense::query()
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Member Summary
        |--------------------------------------------------------------------------
        */

        $totalMembers = Member::query()
            ->count();

        // Member must be approved and its related User must be active.
        $activeMembers = Member::query()
            ->where('status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->count();

        $pendingApplications = Member::query()
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Project Summary
        |--------------------------------------------------------------------------
        */

        $totalProjects = Project::query()
            ->count();

        $activeProjects = Project::query()
            ->where('status', 'in_progress')
            ->count();

        $completedProjects = Project::query()
            ->where('status', 'complete')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Applications
        |--------------------------------------------------------------------------
        */

        $recentApplications = Member::query()
            ->with('user')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Donations
        |--------------------------------------------------------------------------
        */

        $recentDonations = Donation::query()
            ->with('paymentMethod')
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Contribution Payments
        |--------------------------------------------------------------------------
        */

        $recentContributionPayments = ContributionPayment::query()
            ->select([
                'id',
                'contribution_id',
                'member_id',
                'payment_method_id',
                'amount',
                'transaction_id',
                'payment_date',
                'status',
                'created_at',
            ])
            ->with([
                'member:id,user_id,full_name_bn',
                'member.user:id,name',
                'paymentMethod:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Activities
        |--------------------------------------------------------------------------
        */

        $recentDonationActivities = $recentDonations
            ->map(function ($donation) {

                return [
                    'type' => 'donation',
                    'title' => 'Donation received',

                    'name' => $donation->full_name
                        ?? 'Anonymous',

                    'amount' => (float) $donation->amount,

                    'status' => $donation->status,

                    'date' => $donation->created_at,
                ];
            });

        $recentPaymentActivities = $recentContributionPayments
            ->map(function ($payment) {

                return [
                    'type' => 'contribution',
                    'title' => 'Contribution payment',

                    'name' => $payment->member?->user?->name
                        ?? $payment->member?->full_name_bn
                        ?? 'Member',

                    'amount' => (float) $payment->amount,

                    'status' => $payment->status,

                    'date' => $payment->created_at,
                ];
            });

        $recentActivities = $recentDonationActivities
            ->concat($recentPaymentActivities)
            ->sortByDesc('date')
            ->take(8)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' => 'Dashboard data retrieved successfully.',

            'data' => [

                'summary' => [

                    'members' => [
                        'total' => $totalMembers,
                        'active' => $activeMembers,
                        'pending_applications' => $pendingApplications,
                    ],

                    'projects' => [
                        'total' => $totalProjects,
                        'active' => $activeProjects,
                        'completed' => $completedProjects,
                    ],

                    'donations' => [
                        'total_amount' => round(
                            (float) $totalDonations,
                            2
                        ),

                        'pending_amount' => round(
                            (float) $pendingDonations,
                            2
                        ),

                        'approved_count' => $donationCount,

                        'pending_count' => $pendingDonationCount,
                    ],

                    'contributions' => [
                        'total_records' => (int) (
                            $contributionSummary->total_records ?? 0
                        ),

                        'total_required' => round(
                            (float) ($contributionSummary->total_required ?? 0),
                            2
                        ),

                        'total_waived' => round(
                            (float) ($contributionSummary->total_waived ?? 0),
                            2
                        ),

                        'total_paid' => round(
                            (float) ($contributionSummary->total_paid ?? 0),
                            2
                        ),

                        'total_pending' => round(
                            (float) ($contributionSummary->total_pending ?? 0),
                            2
                        ),

                        'total_due' => round(
                            (float) ($contributionSummary->total_due ?? 0),
                            2
                        ),
                    ],

                    'expenses' => [
                        'total_amount' => round(
                            (float) $totalExpenses,
                            2
                        ),
                    ],
                ],

                'monthly_contributions' => $monthlyContributions,

                'recent_applications' => $recentApplications,

                'recent_donations' => $recentDonations,

                'recent_contribution_payments' => $recentContributionPayments,

                'recent_activities' => $recentActivities,

                'filters' => [
                    'year' => (int) $year,
                ],
            ],
        ]);
    }
}
