<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\PaymentMethodRequest;
use App\Http\Requests\PaymentMethodUpdateRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Store a newly created payment method.
     */
    public function store(PaymentMethodRequest $request): JsonResponse
    {
        $data = $request->validated();

        $paymentMethod = PaymentMethod::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method created successfully.',
            'data' => $paymentMethod,
        ], 201);
    }

    /**
     * Get All Payment Methods
     */
    public function index(): JsonResponse
    {
        $paymentMethods = PaymentMethod::query()
            ->orderBy('display_order', 'asc')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Payment methods retrieved successfully.',
            'data' => $paymentMethods,
        ], 200);
    }

    /**
     * Get Active Payment Methods (Public)
     */

    public function activeMethods(): JsonResponse 
    {
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods,
        ], 200);
    }

    /**
     * Payment Method Update 
     */

    public function update(
        PaymentMethodUpdateRequest $request,
        PaymentMethod $paymentMethod
    ): JsonResponse {

        $data = $request->validated();

        // Merge current values with incoming changes
        $updatedData = array_merge(
            $paymentMethod->only([
                'name',
                'type',
                'account_number',
                'account_holder',
                'description',
                'steps',
                'is_active',
            ]),
            $data
        );

        // Validate required configuration when active
        if ($updatedData['is_active']) {

            $type = $updatedData['type'];

            if (
                in_array($type, ['mobile', 'bank']) &&
                empty(trim($updatedData['account_number'] ?? ''))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account number is required to activate this payment method.',
                ], 422);
            }

            if (
                $type === 'bank' &&
                empty(trim($updatedData['account_holder'] ?? ''))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account holder is required for bank transfer.',
                ], 422);
            }

            if (empty(trim($updatedData['description'] ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment instructions are required before activation.',
                ], 422);
            }

            $hasSteps = collect($updatedData['steps'] ?? [])
                ->contains(
                    fn($step) =>
                    is_array($step) &&
                        filled($step['text'] ?? null)
                );

            if (!$hasSteps) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one instruction step is required before activation.',
                ], 422);
            }
        }

        $paymentMethod->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully.',
            'data' => $paymentMethod->fresh(),
        ]);
    }


}
