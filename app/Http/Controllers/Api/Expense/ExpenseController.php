<?php

namespace App\Http\Controllers\Api\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /**
     * Get all expenses
     */
    public function index(Request $request)
    {
        $query = Expense::with([
            'category:id,name,slug',
            'project:id,name',
            'creator:id,name',
        ]);

        // Search by title or paid_to
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%");
            });
        }

        // Filter by expense type
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        // Filter by category
        if ($request->filled('expense_category_id')) {
            $query->where(
                'expense_category_id',
                $request->expense_category_id
            );
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->to_date);
        }

        $expenses = $query
            ->latest('expense_date')
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved successfully.',
            'data' => $expenses,
        ]);
    }

    /**
     * Store a new expense
     */
    public function store(ExpenseRequest $request)
    {
        $validated = $request->validated();

        $receiptPath = null;

        DB::beginTransaction();

        try {
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')
                    ->store('expenses/receipts', 'public');
            }

            $expense = Expense::create([
                'title' => $validated['title'],
                'expense_category_id' => $validated['expense_category_id'],
                'expense_type' => $validated['expense_type'],
                'project_id' => $validated['project_id'] ?? null,
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'],
                'paid_to' => $validated['paid_to'] ?? null,
                'description' => $validated['description'] ?? null,
                'receipt_path' => $receiptPath,
                'created_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'data' => $expense->load([
                    'category:id,name,slug',
                    'project:id,name',
                    'creator:id,name',
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get single expense
     */
    public function show($id)
    {
        $expense = Expense::with([
            'category:id,name,slug',
            'project:id,name',
            'creator:id,name',
        ])->find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense retrieved successfully.',
            'data' => $expense,
        ]);
    }

    /**
     * Update expense
     */
    public function update(ExpenseRequest $request, $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        $validated = $request->validated();

        $oldReceiptPath = $expense->receipt_path;
        $newReceiptPath = null;

        DB::beginTransaction();

        try {
            if ($request->hasFile('receipt')) {
                $newReceiptPath = $request->file('receipt')
                    ->store('expenses/receipts', 'public');
            }

            $expense->update([
                'title' => $validated['title'],
                'expense_category_id' => $validated['expense_category_id'],
                'expense_type' => $validated['expense_type'],
                'project_id' => $validated['project_id'] ?? null,
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'],
                'paid_to' => $validated['paid_to'] ?? null,
                'description' => $validated['description'] ?? null,
                'receipt_path' => $newReceiptPath ?? $oldReceiptPath,
            ]);

            DB::commit();

            if ($newReceiptPath && $oldReceiptPath) {
                Storage::disk('public')->delete($oldReceiptPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'data' => $expense->fresh()->load([
                    'category:id,name,slug',
                    'project:id,name',
                    'creator:id,name',
                ]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newReceiptPath) {
                Storage::disk('public')->delete($newReceiptPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete expense
     */
    public function destroy($id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }
}
