<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFounderRequest;
use App\Http\Requests\UpdateFounderRequest;
use App\Models\Founder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FounderController extends Controller
{
    /**
     * Admin: Get all founders.
     */
    public function index(Request $request)
    {
        $founders = Founder::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(
                min(max((int) $request->input('per_page', 10), 1), 100)
            );

        return response()->json([
            'success' => true,
            'message' => 'Founders retrieved successfully.',
            'data' => $founders,
        ]);
    }

    /**
     * Admin: Store a new founder.
     */
    public function store(StoreFounderRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('founders', 'public');
        }

        $founder = Founder::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Founder created successfully.',
            'data' => $founder->fresh(),
        ], 201);
    }

    /**
     * Admin: Get founder details.
     */
    public function show(string $id)
    {
        $founder = Founder::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Founder retrieved successfully.',
            'data' => $founder,
        ]);
    }

    /**
     * Admin: Update founder.
     */
    public function update(UpdateFounderRequest $request, string $id)
    {
        $founder = Founder::findOrFail($id);

        $data = $request->validated();

        unset($data['remove_photo']);

        // Remove old photo when a new photo is uploaded.
        if ($request->hasFile('photo')) {

            if ($founder->photo) {
                Storage::disk('public')->delete($founder->photo);
            }

            $data['photo'] = $request->file('photo')
                ->store('founders', 'public');
        } elseif ($request->boolean('remove_photo')) {

            // Remove existing photo.
            if ($founder->photo) {
                Storage::disk('public')->delete($founder->photo);
            }

            $data['photo'] = null;
        }

        $founder->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Founder updated successfully.',
            'data' => $founder->fresh(),
        ]);
    }

    /**
     * Admin: Delete founder.
     */
    public function destroy(string $id)
    {
        $founder = Founder::findOrFail($id);

        if ($founder->photo) {
            Storage::disk('public')->delete($founder->photo);
        }

        $founder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Founder deleted successfully.',
        ]);
    }

    /**
     * Public: Get published founders.
     */
    public function publicIndex()
    {
        $founders = Founder::query()
            ->where('status', 'published')
            ->where('confirmed', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Founders retrieved successfully.',
            'data' => $founders,
        ]);
    }

    /**
     * Public: Get a single published founder.
     */
    public function publicShow(string $id)
    {
        $founder = Founder::query()
            ->where('status', 'published')
            ->where('confirmed', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Founder retrieved successfully.',
            'data' => $founder,
        ]);
    }
}
