<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicDonationController extends Controller
{
    // Donation Summary
    public function summary()
    {
        // Only approved and non-deleted donations
        $donations = DB::table('donations')
            ->where('status', 'approved')
            ->whereNull('deleted_at');

        // Only approved and non-deleted expenses
        $expenses = DB::table('expenses')
            ->whereNull('deleted_at');

        // Calculate totals
        $totalDonations = (clone $donations)->sum('amount');
        $totalExpenses = (clone $expenses)->sum('amount');

        // Calculate counts
        $donationCount = (clone $donations)->count();
        $expenseCount = (clone $expenses)->count();

        // Calculate available balance
        $balance = $totalDonations - $totalExpenses;

        // Last updated from approved records
        $donationUpdated = (clone $donations)->max('updated_at');
        $expenseUpdated = (clone $expenses)->max('updated_at');

        $dates = array_filter([
            $donationUpdated,
            $expenseUpdated,
        ]);

        $lastUpdated = !empty($dates)
            ? Carbon::parse(max($dates))->format('Y-m-d')
            : null;

        // Reporting publication control
        $published = config(
            'proyash.financial_report_published',
            false
        );

        // Do not expose figures before publication
        if (!$published) {
            return response()->json([
                'status' => true,
                'data' => [
                    'published' => false,
                    'currency' => 'BDT',
                    'summary' => null,
                    'reporting_period' => null,
                    'last_updated' => null,
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'published' => true,
                'currency' => 'BDT',

                'summary' => [
                    'total_donations' => (float) $totalDonations,
                    'total_expenses' => (float) $totalExpenses,
                    'available_balance' => (float) $balance,
                    'approved_donation_count' => $donationCount,
                    'approved_expense_count' => $expenseCount,
                ],

                'reporting_period' => 'All time',
                'last_updated' => $lastUpdated,
            ],
        ]);
    }
}
