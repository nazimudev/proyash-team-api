<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with([
            'category',
            'images',
            'activities',
        ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully.',
            'data' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(ProjectRequest $request)
    {
        $uploadedFiles = [];

        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // Upload cover image
            $imagePath = null;

            if ($request->hasFile('cover_image')) {
                $image = $request->file('cover_image');
                $imageName = Str::uuid() . '.' . $image->extension();

                $imagePath = $image->storeAs(
                    'projects',
                    $imageName,
                    'public'
                );

                if (!$imagePath) {
                    throw new \RuntimeException('Cover image upload failed.');
                }

                $uploadedFiles[] = $imagePath;
            }

            // Create Project
            $project = Project::create([
                'name' => $validated['project_name'],
                'project_id' => $validated['project_id'] ?? null,
                'category_id' => $validated['project_category_id'],
                'description' => $validated['description'],
                'cover_image' => $imagePath,

                'location' => $validated['location'],
                'responsible_person' => $validated['responsible_person'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,

                'start_date' => $validated['start_date'],
                'expected_end_date' => $validated['expected_end_date'],

                'allocated_budget' => $validated['allocated_budget'],
                'funding_source' => $validated['funding_source'] ?? null,
                'funding_details' => $validated['funding_details'] ?? null,

                'status' => $validated['status'],
                'progress' => $validated['progress'],
            ]);

            // Upload Multiple Gallery Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $imageName = Str::uuid() . '.' . $image->extension();

                    $path = $image->storeAs(
                        'projects/gallery',
                        $imageName,
                        'public'
                    );

                    if (!$path) {
                        throw new \RuntimeException('Gallery image upload failed.');
                    }

                    $uploadedFiles[] = $path;

                    $project->images()->create([
                        'image_path' => $path,
                        'title' => null,
                        'alt_text' => $validated['project_name'],
                        'sort_order' => $index,
                    ]);
                }
            }

            // Store Project Activities
            foreach ($validated['activities'] ?? [] as $index => $activity) {
                $project->activities()->create([
                    'title' => $activity['title'],
                    'description' => $activity['description'] ?? null,
                    'activity_date' => $activity['activity_date'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            // Return Project with relationships
            $project->load([
                'category',
                'images',
                'activities',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.',
                'data' => $project,
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            // Clean up uploaded files
            if (!empty($uploadedFiles)) {
                Storage::disk('public')->delete($uploadedFiles);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the project.',
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with('category')->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project retrieved successfully.',
            'data' => $project,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectRequest $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validated();

        // Keep existing image
        $imagePath = $project->cover_image;

        // Upload new image if provided
        if ($request->hasFile('cover_image')) {
            $imagePath = $request->file('cover_image')
                ->store('projects', 'public');
        }

        // Update project
        $project->update([
            'name' => $validated['project_name'],
            'project_id' => $validated['project_id'] ?? $project->project_id,
            'category_id' => $validated['project_category_id'],
            'description' => $validated['description'],
            'cover_image' => $imagePath,
            'location' => $validated['location'],
            'responsible_person' => $validated['responsible_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'start_date' => $validated['start_date'],
            'expected_end_date' => $validated['expected_end_date'],
            'allocated_budget' => $validated['allocated_budget'],
            'funding_source' => $validated['funding_source'] ?? null,
            'status' => $validated['status'] ?? null,
            'progress' => $validated['progress'] ?? null,
        ]);

        // Delete old image after successful update
        if (
            $request->hasFile('cover_image') &&
            $project->getOriginal('cover_image')
        ) {
            // Old image path is handled below
        }

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => $project->fresh()->load('category'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $project = Project::findOrFail($id);

        // Delete cover image
        if ($project->cover_image) {
            Storage::disk('public')->delete($project->cover_image);
        }

        // Delete project
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }



    public function index_public()
    {
        $projects = Project::with([
            'category',
            'images',
            'activities',
        ])
            ->where('status', 'complete')
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully.',
            'data' => $projects,
        ]);
    }

    public function show_public($id)
    {
        $project = Project::with([
            'category',
            'images',
            'activities',
        ])
            ->where('status', 'complete')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Project retrieved successfully.',
            'data' => $project,
        ]);
    }
}
