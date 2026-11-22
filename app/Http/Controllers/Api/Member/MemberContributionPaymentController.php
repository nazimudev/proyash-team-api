<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\ContributionPayment;
use App\Models\ContributionPaymentLog;
use App\Models\MonthlyContribution;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MemberContributionPaymentController extends Controller
{
    /**
     * Get active online payment methods.
     */
    public function paymentMethods()
    {
        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) != ?', ['cash'])
            ->whereRaw('LOWER(type) != ?', ['cash'])
            ->orderBy('display_order')
            ->get([
                'id',
                'name',
                'type',
                'account_number',
                'account_holder',
                'description',
                'steps',
                'requires_transaction_id',
                'requires_phone',
                'requires_payment_proof',
            ]);

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Submit a contribution payment.
     */
    public function store(Request $request, $contributionId)
    {
        // Find the authenticated member.
        $member = auth()->user()
            ->member()
            ->firstOrFail();

        // Validate payment method ID first.
        $request->validate([
            'payment_method_id' => [
                'required',
                'integer',
                'exists:payment_methods,id',
            ],
        ]);

        // Only active non-cash methods are allowed.
        $method = PaymentMethod::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) != ?', ['cash'])
            ->whereRaw('LOWER(type) != ?', ['cash'])
            ->findOrFail($request->payment_method_id);

        // Dynamic validation based on payment method settings.
        $validated = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'transaction_id' => [
                $method->requires_transaction_id ? 'required' : 'nullable',
                'string',
                'max:100',
            ],
            'phone_number' => [
                $method->requires_phone ? 'required' : 'nullable',
                'string',
                'max:20',
            ],
            'receipt' => [
                $method->requires_payment_proof ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')
                ->store('contribution-receipts', 'public');
        }

        try {
            $payment = DB::transaction(function () use (
                $member,
                $contributionId,
                $method,
                $validated,
                $receiptPath
            ) {
                $contribution = MonthlyContribution::query()
                    ->where('id', $contributionId)
                    ->where('member_id', $member->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $payableAmount = (float) $contribution->required_amount
                    - (float) $contribution->waived_amount;

                // Approved and pending payments both reserve balance.
                $reservedAmount = (float) $contribution->payments()
                    ->whereIn('status', ['pending', 'approved'])
                    ->sum('amount');

                $remainingAmount = max(
                    0,
                    $payableAmount - $reservedAmount
                );

                if ((float) $validated['amount'] > $remainingAmount) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'Payment amount exceeds the remaining contribution balance.',
                        ],
                    ]);
                }

                $payment = ContributionPayment::create([
                    'contribution_id' => $contribution->id,
                    'member_id' => $member->id,
                    'payment_method_id' => $method->id,
                    'amount' => $validated['amount'],
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'phone_number' => $validated['phone_number'] ?? null,
                    'receipt_path' => $receiptPath,
                    'payment_date' => $validated['payment_date'],
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                ContributionPaymentLog::create([
                    'payment_id' => $payment->id,
                    'old_status' => null,
                    'new_status' => 'pending',
                    'changed_by' => auth()->id(),
                    'note' => 'Payment submitted by member.',
                ]);

                return $payment;
            });
        } catch (\Throwable $exception) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Contribution payment submitted successfully. Waiting for admin verification.',
            'data' => $payment->load('paymentMethod'),
        ], 201);
    }

    /**
     * Get authenticated member's payment history.
     */

    public function history(Request $request)
    {
        $member = $request->user()
            ->member()
            ->firstOrFail();

        // Validate filters
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:verified,pending,rejected'],
            'method' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Base query: only authenticated member's payments
        $baseQuery = ContributionPayment::query()
            ->where('member_id', $member->id);

        /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    | Summary counts all payments, regardless of active filters.
    */

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as verified
        ")
            ->selectRaw("
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        ")
            ->selectRaw("
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        ")
            ->first();

        /*
    |--------------------------------------------------------------------------
    | Payment List Query
    |--------------------------------------------------------------------------
    */

        $query = (clone $baseQuery)
            ->with([
                'paymentMethod',
                'contribution',
                'verifier',
            ]);

        // Search by transaction ID or payment reference (ID)
        if (!empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        // Frontend verified = Backend approved
        if (!empty($validated['status'])) {
            $status = $validated['status'] === 'verified'
                ? 'approved'
                : $validated['status'];

            $query->where('status', $status);
        }

        // Filter by payment method
        if (!empty($validated['method'])) {
            $query->whereHas('paymentMethod', function ($q) use ($validated) {
                $q->where('name', $validated['method']);
            });
        }

        // Date filters
        if (!empty($validated['date_from'])) {
            $query->whereDate(
                'payment_date',
                '>=',
                $validated['date_from']
            );
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate(
                'payment_date',
                '<=',
                $validated['date_to']
            );
        }

        // Pagination
        $perPage = $validated['per_page'] ?? 6;
        $page = $validated['page'] ?? 1;

        $payments = $query
            ->latest('payment_date')
            ->latest('id')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        /*
    |--------------------------------------------------------------------------
    | Transform Payment Data
    |--------------------------------------------------------------------------
    */

        $paymentData = $payments->getCollection()
            ->map(function ($payment) {

                return [
                    'id' => $payment->id,

                    'contributionId' => $payment->contribution_id,

                    'year' => $payment->contribution?->year,

                    'month' => $payment->contribution?->month,

                    'amount' => (float) $payment->amount,

                    'method' => $payment->paymentMethod?->name,

                    'transactionId' => $payment->transaction_id,

                    // No separate receipt number field confirmed yet.
                    'receiptNo' => null,

                    'paymentDate' => $payment->payment_date
                        ? date('Y-m-d', strtotime($payment->payment_date))
                        : null,

                    'status' => $payment->status === 'approved'
                        ? 'verified'
                        : $payment->status,

                    'verifiedBy' => $payment->verifier?->name,

                    'verifiedAt' => $payment->verified_at,

                    'rejectionReason' => $payment->status === 'rejected'
                        ? $payment->notes
                        : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,

            'data' => [
                'payments' => $paymentData,

                'summary' => [
                    'total' => (int) $summary->total,
                    'verified' => (int) $summary->verified,
                    'pending' => (int) $summary->pending,
                    'rejected' => (int) $summary->rejected,
                ],

                'pagination' => [
                    'page' => $payments->currentPage(),
                    'pageSize' => $payments->perPage(),
                    'total' => $payments->total(),
                    'totalPages' => $payments->lastPage(),
                ],
            ],
        ]);
    }
}
