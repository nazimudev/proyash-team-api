<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryPhotoRequest;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryPhotoController extends Controller
{
    /**
     * Get all gallery photos for admin.
     */
    public function index()
    {
        $photos = GalleryPhoto::query()
            ->latest('created_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Gallery photos retrieved successfully.',
            'data' => $photos,
        ]);
    }

    /**
     * Create a new gallery photo.
     */
    public function store(GalleryPhotoRequest $request)
    {
        $validated = $request->validated();

        $imagePath = $request->file('image')->store(
            config('gallery.image.directory'),
            config('gallery.image.disk')
        );

        try {
            $photo = DB::transaction(function () use (
                $validated,
                $imagePath
            ) {
                $validated['image_path'] = $imagePath;

                return GalleryPhoto::create($validated);
            });

            return response()->json([
                'success' => true,
                'message' => 'Gallery photo created successfully.',
                'data' => $photo,
            ], 201);
        } catch (\Throwable $e) {

            Storage::disk(config('gallery.image.disk'))
                ->delete($imagePath);

            throw $e;
        }
    }

    /**
     * Update gallery photo.
     */
    public function update(
        GalleryPhotoRequest $request,
        GalleryPhoto $photo
    ) {
        $validated = $request->validated();

        $disk = config('gallery.image.disk');

        $oldImagePath = $photo->image_path;
        $newImagePath = null;

        // Upload replacement image if provided.
        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store(
                config('gallery.image.directory'),
                $disk
            );

            $validated['image_path'] = $newImagePath;
        }

        try {
            $updatedPhoto = DB::transaction(function () use (
                $photo,
                $validated
            ) {
                $photo->update($validated);

                return $photo->fresh();
            });
        } catch (\Throwable $e) {

            // Remove newly uploaded image if update fails.
            if ($newImagePath) {
                Storage::disk($disk)->delete($newImagePath);
            }

            throw $e;
        }

        // Delete old image only after successful database update.
        if ($newImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk($disk)->delete($oldImagePath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Gallery photo updated successfully.',
            'data' => $updatedPhoto,
        ]);
    }

    /**
     * Publish or unpublish a photo.
     */
    public function updateStatus(Request $request, GalleryPhoto $photo)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:draft,published',
            ],
        ]);

        $photo->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery photo status updated successfully.',
            'data' => $photo->fresh(),
        ]);
    }

    /**
     * Delete gallery photo and its image.
     */
    public function destroy(GalleryPhoto $photo)
    {
        $imagePath = $photo->image_path;

        DB::transaction(function () use ($photo) {
            $photo->delete();
        });

        // Delete image after successful database deletion.
        Storage::disk(config('gallery.image.disk'))
            ->delete($imagePath);

        return response()->json([
            'success' => true,
            'message' => 'Gallery photo deleted successfully.',
        ]);
    }
}
