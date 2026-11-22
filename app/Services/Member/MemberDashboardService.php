<?php

namespace App\Services\Member;

use App\Models\MonthlyContribution;
use App\Models\ContributionPayment;
use App\Models\Member;
use Illuminate\Support\Collection;

class MemberDashboardService
{
    public function getDashboard(Member $member): array
    {
        $contributions = MonthlyContribution::query()
            ->where('member_id', $member->id)
            ->with([
                'payments' => function ($query) {
                    $query->whereIn('status', [
                        'approved',
                        'pending',
                    ]);
                },
            ])
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $records = $contributions->map(function ($contribution) {
            $required = (float) $contribution->required_amount;
            $waived = (float) $contribution->waived_amount;

            $payable = max(0, $required - $waived);

            $paid = (float) $contribution->payments
                ->where('status', 'approved')
                ->sum('amount');

            $pending = (float) $contribution->payments
                ->where('status', 'pending')
                ->sum('amount');

            $due = max(0, $payable - $paid - $pending);

            $status = 'due';

            if ($payable > 0 && $paid >= $payable) {
                $status = 'paid';
            } elseif ($pending > 0) {
                $status = 'pending_verification';
            } elseif ($paid > 0) {
                $status = 'partial';
            }

            return [
                'id' => $contribution->id,
                'year' => (int) $contribution->year,
                'month' => (int) $contribution->month,
                'requiredAmount' => $payable,
                'paidAmount' => $paid,
                'pendingAmount' => $pending,
                'dueAmount' => $due,
                'status' => $status,
            ];
        });

        $expectedAmount = (float) $contributions->sum(
            fn($item) => max(
                0,
                (float) $item->required_amount -
                    (float) $item->waived_amount
            )
        );

        $paidAmount = (float) $records->sum('paidAmount');
        $dueAmount = (float) $records->sum('dueAmount');
        $pendingAmount = (float) $records->sum('pendingAmount');

        $paidMonths = $records
            ->where('status', 'paid')
            ->count();

        $dueMonths = $records
            ->where('dueAmount', '>', 0)
            ->count();

        $pendingMonths = $records
            ->where('pendingAmount', '>', 0)
            ->count();

        $completionPercent = $expectedAmount > 0
            ? round(($paidAmount / $expectedAmount) * 100, 2)
            : 0;

        $monthlyChart = $records->map(function ($record) {
            return [
                'year' => $record['year'],
                'month' => $record['month'],
                'requiredAmount' => $record['requiredAmount'],
                'paidAmount' => $record['paidAmount'],
                'pendingAmount' => $record['pendingAmount'],
                'dueAmount' => $record['dueAmount'],
                'status' => $record['status'],
            ];
        })->values();

        $recentPayments = ContributionPayment::query()
            ->where('member_id', $member->id)
            ->with([
                'paymentMethod',
                'contribution',
            ])
            ->latest('payment_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $upcomingDue = $records
            ->filter(fn($record) => $record['dueAmount'] > 0)
            ->sortBy([
                ['year', 'asc'],
                ['month', 'asc'],
            ])
            ->first();

        if ($upcomingDue) {
            $upcomingDue = [
                'id' => $upcomingDue['id'],
                'year' => $upcomingDue['year'],
                'month' => $upcomingDue['month'],
                'requiredAmount' => $upcomingDue['requiredAmount'],
                'paidAmount' => $upcomingDue['paidAmount'],
                'pendingAmount' => $upcomingDue['pendingAmount'],
                'dueAmount' => $upcomingDue['dueAmount'],
                'status' => $upcomingDue['status'],
            ];
        }

        return [
            'summary' => [
                'expectedAmount' => $expectedAmount,
                'paidAmount' => $paidAmount,
                'dueAmount' => $dueAmount,
                'pendingAmount' => $pendingAmount,
                'outstandingAmount' => $dueAmount + $pendingAmount,
                'paidMonths' => $paidMonths,
                'dueMonths' => $dueMonths,
                'pendingMonths' => $pendingMonths,
                'completionPercent' => min(100, $completionPercent),
            ],

            'monthlyChart' => $monthlyChart,

            'recentPayments' => $recentPayments,

            'upcomingDue' => $upcomingDue,
        ];
    }
}
