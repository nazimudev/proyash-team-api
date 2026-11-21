<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContributionPayment;
use App\Models\ContributionPaymentLog;
use App\Models\Member;
use App\Models\MonthlyContribution;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminContributionPaymentController extends Controller
{
    /**
     * Get all contribution payments.
     * Optional filter: ?status=pending
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:pending,approved,rejected',
            ],
        ]);

        $payments = ContributionPayment::query()
            ->with([
                'member',
                'paymentMethod',
                'contribution',
                'verifier',
            ])
            ->when(
                $validated['status'] ?? null,
                fn($query, $status) => $query->where('status', $status)
            )
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get a single payment details.
     */
    public function show($id)
    {
        $payment = ContributionPayment::query()
            ->with([
                'member.user',
                'paymentMethod',
                'contribution',
                'verifier',
                'logs.changedBy',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Approve a pending online payment.
     */
    public function approve($id)
    {
        // Read contribution ID first.
        $existingPayment = ContributionPayment::findOrFail($id);

        $adminId = auth()->id();

        $payment = DB::transaction(function () use (
            $id,
            $existingPayment,
            $adminId
        ) {
            // Lock contribution first to serialize balance changes.
            $contribution = MonthlyContribution::query()
                ->whereKey($existingPayment->contribution_id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = ContributionPayment::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => [
                        'Only pending payments can be approved.',
                    ],
                ]);
            }

            $payableAmount = (float) $contribution->required_amount
                - (float) $contribution->waived_amount;

            // Include other pending and approved payments.
            $reservedAmount = (float) $contribution->payments()
                ->whereIn('status', ['pending', 'approved'])
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            if ($reservedAmount + (float) $payment->amount > $payableAmount) {
                throw ValidationException::withMessages([
                    'payment' => [
                        'Approving this payment would exceed the contribution balance.',
                    ],
                ]);
            }

            $oldStatus = $payment->status;

            $payment->update([
                'status' => 'approved',
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            ContributionPaymentLog::create([
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'changed_by' => $adminId,
                'note' => 'Payment approved by admin.',
            ]);

            return $payment->fresh([
                'member',
                'paymentMethod',
                'contribution',
                'verifier',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Contribution payment approved successfully.',
            'data' => $payment,
        ]);
    }

    /**
     * Reject a pending payment.
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $existingPayment = ContributionPayment::findOrFail($id);
        $adminId = auth()->id();

        $payment = DB::transaction(function () use (
            $id,
            $existingPayment,
            $adminId,
            $validated
        ) {
            MonthlyContribution::query()
                ->whereKey($existingPayment->contribution_id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = ContributionPayment::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => [
                        'Only pending payments can be rejected.',
                    ],
                ]);
            }

            $oldStatus = $payment->status;

            $payment->update([
                'status' => 'rejected',
                'verified_by' => $adminId,
                'verified_at' => now(),
                'notes' => trim(
                    ($payment->notes ? $payment->notes . "\n\n" : '')
                        . 'Rejection reason: '
                        . $validated['reason']
                ),
            ]);

            ContributionPaymentLog::create([
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'changed_by' => $adminId,
                'note' => $validated['reason'],
            ]);

            return $payment->fresh([
                'member',
                'paymentMethod',
                'contribution',
                'verifier',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Contribution payment rejected successfully.',
            'data' => $payment,
        ]);
    }

    /**
     * Admin records a cash payment received directly.
     * Cash payment is approved immediately.
     */
    public function storeCashPayment(Request $request)
    {
        $validated = $request->validate([
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],
            'contribution_id' => [
                'required',
                'integer',
                'exists:monthly_contributions,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        // Find the existing Cash Payment Method.
        $cashMethod = PaymentMethod::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['cash'])
                    ->orWhereRaw('LOWER(type) = ?', ['cash']);
            })
            ->first();

        if (!$cashMethod) {
            throw ValidationException::withMessages([
                'payment_method' => [
                    'Cash payment method is not configured.',
                ],
            ]);
        }

        $adminId = auth()->id();

        $payment = DB::transaction(function () use (
            $validated,
            $cashMethod,
            $adminId
        ) {
            $contribution = MonthlyContribution::query()
                ->whereKey($validated['contribution_id'])
                ->where('member_id', $validated['member_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $payableAmount = (float) $contribution->required_amount
                - (float) $contribution->waived_amount;

            // Pending payments also reserve the remaining balance.
            $reservedAmount = (float) $contribution->payments()
                ->whereIn('status', ['pending', 'approved'])
                ->sum('amount');

            if (
                $reservedAmount + (float) $validated['amount']
                > $payableAmount
            ) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Cash payment exceeds the remaining contribution balance.',
                    ],
                ]);
            }

            $payment = ContributionPayment::create([
                'contribution_id' => $contribution->id,
                'member_id' => $contribution->member_id,
                'payment_method_id' => $cashMethod->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'status' => 'approved',
                'notes' => $validated['notes'] ?? null,
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            ContributionPaymentLog::create([
                'payment_id' => $payment->id,
                'old_status' => null,
                'new_status' => 'approved',
                'changed_by' => $adminId,
                'note' => 'Cash payment received and recorded by admin.',
            ]);

            return $payment->fresh([
                'member',
                'paymentMethod',
                'contribution',
                'verifier',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cash contribution payment recorded successfully.',
            'data' => $payment,
        ], 201);
    }
}
