<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyContribution;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminContributionController extends Controller
{
    /**
     * Base query with payment aggregates.
     */
    private function contributionQuery()
    {
        return MonthlyContribution::query()
            ->with([
                'member.user',
            ])
            ->withSum([
                'payments as approved_paid_amount' => function ($query) {
                    $query->where('status', 'approved');
                }
            ], 'amount')
            ->withSum([
                'payments as pending_payment_amount' => function ($query) {
                    $query->where('status', 'pending');
                }
            ], 'amount');
    }

    /**
     * Apply common filters.
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        return $query;
    }

    /**
     * Calculate contribution amounts and status.
     */
    private function formatContribution($item)
    {
        $required = (float) $item->required_amount;
        $waived = (float) $item->waived_amount;

        $paid = (float) ($item->approved_paid_amount ?? 0);
        $pending = (float) ($item->pending_payment_amount ?? 0);

        $payable = max(0, $required - $waived);
        $due = max(0, $payable - $paid);
        $available = max(0, $due - $pending);

        if ($due <= 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } elseif ($pending > 0) {
            $status = 'pending';
        } else {
            $status = 'due';
        }

        $item->month_name = date(
            'F',
            mktime(0, 0, 0, $item->month, 1)
        );

        $item->payable_amount = $payable;
        $item->paid_amount = $paid;
        $item->pending_amount = $pending;
        $item->due_amount = $due;
        $item->available_payment_balance = $available;
        $item->status = $status;

        return $item;
    }

    /**
     * Calculate summary from contribution records.
     */
    private function calculateSummary(Collection $contributions)
    {
        $totalRequired = 0;
        $totalWaived = 0;
        $totalPaid = 0;
        $totalPending = 0;
        $totalDue = 0;

        foreach ($contributions as $item) {
            $required = (float) $item->required_amount;
            $waived = (float) $item->waived_amount;

            $paid = (float) ($item->approved_paid_amount ?? 0);
            $pending = (float) ($item->pending_payment_amount ?? 0);

            $payable = max(0, $required - $waived);

            $totalRequired += $required;
            $totalWaived += $waived;
            $totalPaid += $paid;
            $totalPending += $pending;
            $totalDue += max(0, $payable - $paid);
        }

        return [
            'total_contributions' => $contributions->count(),
            'total_required' => round($totalRequired, 2),
            'total_waived' => round($totalWaived, 2),
            'total_paid' => round($totalPaid, 2),
            'total_pending' => round($totalPending, 2),
            'total_due' => round($totalDue, 2),
        ];
    }

    /**
     * GET: All contributions with filters.
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'member_id' => 'nullable|integer|exists:members,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->applyFilters(
            $this->contributionQuery(),
            $request
        );

        $contributions = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($request->input('per_page', 15));

        $contributions->getCollection()->transform(function ($item) {
            return $this->formatContribution($item);
        });

        return response()->json([
            'success' => true,
            'data' => $contributions,
        ]);
    }

    /**
     * GET: Overall contribution summary.
     */
    public function summary(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'member_id' => 'nullable|integer|exists:members,id',
        ]);

        $query = $this->applyFilters(
            $this->contributionQuery(),
            $request
        );

        $contributions = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->calculateSummary($contributions),
            ],
        ]);
    }

    /**
     * GET: Specific member contribution history.
     */
    public function memberHistory(Request $request, $memberId)
    {
        $member = Member::with('user')->findOrFail($memberId);

        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->contributionQuery()
            ->where('member_id', $memberId);

        $query = $this->applyFilters($query, $request);

        $allContributions = (clone $query)->get();

        $contributions = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($request->input('per_page', 12));

        $contributions->getCollection()->transform(function ($item) {
            return $this->formatContribution($item);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'summary' => $this->calculateSummary($allContributions),
                'contributions' => $contributions,
            ],
        ]);
    }

    /**
     * GET: Members who have outstanding contributions.
     */
    public function dueMembers(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->contributionQuery();

        $query = $this->applyFilters($query, $request);

        // A contribution is due when approved payments
        // are less than the payable amount.
        $query->whereRaw(
            '(required_amount - waived_amount) > (
                SELECT COALESCE(SUM(cp.amount), 0)
                FROM contribution_payments cp
                WHERE cp.contribution_id = monthly_contributions.id
                AND cp.status = ?
            )',
            ['approved']
        );

        $contributions = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($request->input('per_page', 15));

        $contributions->getCollection()->transform(function ($item) {
            return $this->formatContribution($item);
        });

        return response()->json([
            'success' => true,
            'data' => $contributions,
        ]);
    }
}
