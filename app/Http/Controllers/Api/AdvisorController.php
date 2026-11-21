<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvisorRequest;
use App\Http\Requests\UpdateAdvisorRequest;
use App\Models\Advisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdvisorController extends Controller
{
    /** GET /api/advisors?page=1&per_page=12 (public) */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);
        $advisors = Advisor::orderBy('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $advisors->items(),
            'meta'    => [
                'current_page' => $advisors->currentPage(),
                'last_page'    => $advisors->lastPage(),
                'per_page'     => $advisors->perPage(),
                'total'        => $advisors->total(),
            ],
        ]);
    }

    /** GET /api/advisors/{id} (public) */
    public function show(int $id): JsonResponse
    {
        $advisor = Advisor::find($id);

        if (! $advisor) {
            return $this->notFound();
        }

        return response()->json(['success' => true, 'data' => $advisor]);
    }

    /** POST /api/admin/advisors */
    public function store(StoreAdvisorRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('advisors', 'public');
        }

        try {
            $advisor = Advisor::create($data);
        } catch (Throwable $e) {
            if (! empty($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }
            return $this->serverError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advisor added successfully.',
            'data'    => $advisor,
        ], 201);
    }

    /** PUT|PATCH|POST(_method=PUT) /api/admin/advisors/{id} */
    public function update(UpdateAdvisorRequest $request, int $id): JsonResponse
    {
        $advisor = Advisor::find($id);

        if (! $advisor) {
            return $this->notFound();
        }

        $data = $request->safe()->except(['image', 'remove_image']);
        $oldImage = $advisor->image;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('advisors', 'public');
            $data['image'] = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        try {
            $advisor->update($data);
        } catch (Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            return $this->serverError($e);
        }

        // Only remove the old file after the database update succeeded.
        if ($oldImage && array_key_exists('image', $data) && $data['image'] !== $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advisor updated successfully.',
            'data'    => $advisor->fresh(),
        ]);
    }

    /** DELETE /api/admin/advisors/{id} */
    public function destroy(int $id): JsonResponse
    {
        $advisor = Advisor::find($id);

        if (! $advisor) {
            return $this->notFound();
        }

        $image = $advisor->image;

        try {
            $advisor->delete();
        } catch (Throwable $e) {
            return $this->serverError($e);
        }

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return response()->json(['success' => true, 'message' => 'Advisor deleted successfully.']);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Advisor not found.'], 404);
    }

    private function serverError(Throwable $e): JsonResponse
    {
        Log::error('Advisor API error: ' . $e->getMessage());

        return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }
}
