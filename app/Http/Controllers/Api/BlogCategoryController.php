<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $categories = BlogCategory::query()->orderBy('name')->get();

        return $this->success(
            BlogCategoryResource::collection($categories)->resolve($request),
            'Categories retrieved successfully.',
        );
    }

    // Store category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name',
        ]);

        $category = BlogCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Blog category created successfully.',
            'data' => $category,
        ], 201);
    }

    // Delete category
    public function destroy($id)
    {
        $category = BlogCategory::findOrFail($id);

        if ($category->blogs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category because blogs are assigned to it.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog category deleted successfully.',
        ]);
    }
}
