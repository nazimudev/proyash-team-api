<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicFinancialLedgerController extends Controller
{
    public function index(): JsonResponse
    {
        // Published and approved donations only.
        $donations = DB::table('donations')
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->select([
                'id',
                'amount',
                'donation_date',
                'created_at',
                'purpose',
            ])
            ->get()
            ->map(function ($donation) {
                $date = $donation->donation_date
                    ?: $donation->created_at;

                return [
                    'id' => 'donation-' . $donation->id,
                    'date' => $date,
                    'dateLabel' => $date
                        ? Carbon::parse($date)->format('d M Y')
                        : null,
                    'description' => $donation->purpose ?: 'Donation',
                    'category' => 'Donation',
                    'incomeMinor' => (int) round(
                        (float) $donation->amount * 100
                    ),
                    'expenseMinor' => null,
                    'statusLabel' => 'Approved',
                    'type' => 'income',
                ];
            });

        // Published and approved expenses only.
        $expenses = DB::table('expenses')
            ->whereNull('deleted_at')
            ->select([
                'id',
                'amount',
                'expense_date',
                'created_at',
                'title',
                'expense_type',
            ])
            ->get()
            ->map(function ($expense) {
                $date = $expense->expense_date ?: $expense->created_at;

                return [
                    'id' => 'expense-' . $expense->id,
                    'date' => $date,
                    'dateLabel' => $date
                        ? Carbon::parse($date)->format('d M Y')
                        : null,
                    'description' => $expense->title ?: 'Project Expense',
                    'category' => $expense->expense_type ?: 'Expense',
                    'incomeMinor' => null,
                    'expenseMinor' => (int) round(
                        (float) $expense->amount * 100
                    ),
                    'statusLabel' => 'Approved',
                    'type' => 'expense',
                ];
            });

        $ledger = $donations
            ->concat($expenses)
            ->filter(fn($item) => $item['date'] !== null)
            ->sortByDesc('date')
            ->values()
            ->map(function ($item) {
                unset($item['date']);

                return $item;
            });

        return response()->json([
            'status' => true,
            'message' => 'Public financial ledger retrieved successfully.',
            'data' => [
                'ledger' => $ledger,
                'total' => $ledger->count(),
            ],
        ]);
    }
}
