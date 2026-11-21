<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationRequest;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    //
    public function store(DonationRequest $request)
    {
        $data = $request->validated();

        $data['status'] = 'pending';

        if ($data['donor_type'] === 'anonymous') {
            $data['full_name'] = null;
        }

        $donation = Donation::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Donation submitted successfully. Awaiting verification.',
            'data' => $donation,
        ], 201);
    }

    /*
    * Get Donation List 
    */
    public function get()
    {
        try {

            $donations = Donation::query()
                ->with('paymentMethod')
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Donation list retrieved successfully.',
                'data' => $donations,
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Failed to retrieve donations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve donations.',
            ], 500);
        }
    }

    /**
     * Verify Donation
     */
    public function verify(Request $request, int $id)
    {
        try {

            $donation = DB::transaction(function () use ($request, $id) {

                $donation = Donation::whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($donation->status !== 'pending') {
                    return null;
                }

                $donation->update([
                    'status' => 'approved',
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);

                return $donation->load('verifiedBy:id,name');
            });

            if (!$donation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only pending donations can be verified.',
                ], 409);
            }

            return response()->json([
                'status' => true,
                'message' => 'Donation verified successfully.',
                'data' => $donation,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Donation not found.',
            ], 404);
        } catch (\Throwable $e) {

            Log::error('Donation verification failed', [
                'donation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to verify donation.',
            ], 500);
        }
    }

    /**
     * Reject Reson
     */

    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $donation = Donation::findOrFail($id);

            if ($donation->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only pending donations can be rejected.',
                ], 409);
            }

            $donation->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Donation rejected successfully.',
                'data' => $donation->fresh(),
            ], 200);
        } catch (\Throwable $e) {

            \Illuminate\Support\Facades\Log::error('Donation rejection failed', [
                'donation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to reject donation.',
            ], 500);
        }
    }

    /**
     * Soft Delete Function
     */
    public function destroy(int $id)
    {
        try {

            $donation = Donation::find($id);

            if (!$donation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Donation not found.',
                ], 404);
            }

            $donation->delete();

            return response()->json([
                'status' => true,
                'message' => 'Donation deleted successfully.',
            ], 200);
        } catch (\Throwable $e) {

            \Illuminate\Support\Facades\Log::error(
                'Donation deletion failed',
                [
                    'donation_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete donation.',
            ], 500);
        }
    }

    /**
     * Donation Edit Only Pending Donation
     */

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'donor_type' => 'sometimes|required|in:named,anonymous',
            'full_name' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:1',
            'purpose' => 'nullable|string|max:255',
            'payment_method_id' => 'sometimes|required|integer|exists:payment_methods,id',
            'transaction_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            $donation = Donation::find($id);

            if (!$donation) {
                return response()->json([
                    'status' => false,
                    'message' => 'Donation not found.',
                ], 404);
            }

            // Only pending donations can be edited
            if ($donation->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only pending donations can be edited.',
                ], 409);
            }

            $donation->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Donation updated successfully.',
                'data' => $donation->fresh(),
            ], 200);
        } catch (\Throwable $e) {

            \Illuminate\Support\Facades\Log::error(
                'Donation update failed',
                [
                    'donation_id' => $id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => false,
                'message' => 'Failed to update donation.',
            ], 500);
        }
    }

    /**
     * Add donation admin
     */

    public function store_admin(Request $request)
    {
        $validated = $request->validate([
            'donor_type' => [
                'required',
                Rule::in(['named', 'anonymous']),
            ],
            'full_name' => [
                'nullable',
                'required_if:donor_type,named',
                'string',
                'max:255',
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')
                    ->where('is_active', true),
            ],
            'transaction_id' => [
                'required',
                'string',
                'max:255',
            ],
            'donation_date' => [
                'required',
                'date',
            ],
            'status' => [
                'required',
                Rule::in(['pending', 'approved']),
            ],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $donation = DB::transaction(function () use ($validated, $request) {

                $isApproved = $validated['status'] === 'approved';

                return Donation::create([
                    'donor_type' => $validated['donor_type'],
                    'full_name' => $validated['donor_type'] === 'anonymous'
                        ? null
                        : $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'amount' => $validated['amount'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'transaction_id' => $validated['transaction_id'],
                    'donation_date' => $validated['donation_date'],
                    'status' => $validated['status'],
                    'message' => $validated['message'] ?? null,
                    'verified_by' => $isApproved
                        ? $request->user()->id
                        : null,
                    'verified_at' => $isApproved
                        ? now()
                        : null,
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Donation created successfully.',
                'data' => $donation->load('paymentMethod'),
            ], 201);
        } catch (\Throwable $e) {

            Log::error('Donation creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create donation.',
            ], 500);
        }
    }

    // Public Donation List
    public function getDonations() {
        try{
            $donations = Donation::all();

            return response()->json([
                'status' => true,
                'data' => $donations,
            ], 200);

        } catch(\Throwable $e){
            return response()->json([
                'status' => false,
                'masseag' => 'Donation Not Show',
            ], 203);
        }
    }
}
