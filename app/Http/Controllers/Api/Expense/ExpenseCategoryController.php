<?php

namespace App\Http\Controllers\Api\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    // Get all expense categories
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Expense categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

    // Store new expense category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ExpenseCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => $category,
        ], 201);
    }

    // Get single expense category
    public function show($id)
    {
        $category = ExpenseCategory::withCount('expenses')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense category retrieved successfully.',
            'data' => $category,
        ]);
    }

    // Update expense category
    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    // Delete expense category
    public function destroy($id)
    {
        $category = ExpenseCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Expense category not found.',
            ], 404);
        }

        // Prevent deletion if expenses exist
        if ($category->expenses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This category cannot be deleted because it has associated expenses.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }
}
