<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\MonthlyContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberContributionOnlyController extends Controller
{
    /**
     * Get member contribution summary and monthly history.
     */
    public function index(Request $request)
    {
        $member = $request->user()
            ->member()
            ->firstOrFail();

        // Get all contributions with approved and pending payment totals.
        $allContributions = $this->contributionQuery($member->id)->get();

        $totalRequired = $allContributions->sum(
            fn($item) => (float) $item->required_amount
        );

        $totalWaived = $allContributions->sum(
            fn($item) => (float) $item->waived_amount
        );

        $totalPaid = $allContributions->sum(
            fn($item) => (float) $item->approved_paid_amount
        );

        $totalPending = $allContributions->sum(
            fn($item) => (float) $item->pending_payment_amount
        );

        $totalDue = $allContributions->sum(function ($item) {
            $payable = (float) $item->required_amount
                - (float) $item->waived_amount;

            $paid = (float) $item->approved_paid_amount;

            return max(0, $payable - $paid);
        });

        // Paginated monthly contribution history.
        $contributions = $this->contributionQuery($member->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        $contributions->getCollection()->transform(
            fn($item) => $this->formatContribution($item)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_contributions' => $allContributions->count(),
                    'total_required' => round($totalRequired, 2),
                    'total_waived' => round($totalWaived, 2),
                    'total_paid' => round($totalPaid, 2),
                    'total_pending' => round($totalPending, 2),
                    'total_due' => round($totalDue, 2),
                ],
                'contributions' => $contributions,
            ],
        ]);
    }

    /**
     * Build contribution query with payment aggregates.
     */
    private function contributionQuery(int $memberId)
    {
        return MonthlyContribution::query()
            ->where('member_id', $memberId)
            ->withSum([
                'payments as approved_paid_amount' =>
                fn($query) => $query->where('status', 'approved')
            ], 'amount')
            ->withSum([
                'payments as pending_payment_amount' =>
                fn($query) => $query->where('status', 'pending')
            ], 'amount');
    }

    /**
     * Calculate individual contribution amounts.
     */
    private function formatContribution($item)
    {
        $required = (float) $item->required_amount;
        $waived = (float) $item->waived_amount;

        $paid = (float) ($item->approved_paid_amount ?? 0);
        $pending = (float) ($item->pending_payment_amount ?? 0);

        $payable = max(0, $required - $waived);
        $due = max(0, $payable - $paid);

        $item->month_name = Carbon::createFromDate(
            $item->year,
            $item->month,
            1
        )->format('F');

        $item->payable_amount = round($payable, 2);
        $item->paid_amount = round($paid, 2);
        $item->pending_amount = round($pending, 2);
        $item->due_amount = round($due, 2);

        // Balance available for a new payment submission.
        $item->available_payment_balance = round(
            max(0, $due - $pending),
            2
        );

        return $item;
    }
}
