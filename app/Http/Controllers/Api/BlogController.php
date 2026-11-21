<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogIndexRequest;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BlogController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly BlogService $service) {}

    public function index(BlogIndexRequest $request): JsonResponse
    {
        $blogs = Blog::query()
            ->with('category')
            ->filter($request->filters())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return $this->paginated($blogs, BlogResource::class, $request, 'Blogs retrieved successfully.');
    }

    public function show(Request $request, Blog $blog): JsonResponse
    {
        return $this->success($this->toArray($blog, $request), 'Blog retrieved successfully.');
    }

    public function store(StoreBlogRequest $request): JsonResponse
    {
        try {
            $blog = $this->service->create($request->blogData(), $request->file('featured_image'));
        } catch (RuntimeException $e) {
            return $this->saveFailed($e);
        }

        return $this->success($this->toArray($blog, $request), 'Blog created successfully.', 201);
    }

    /** The frontend sends POST + _method=PUT (multipart), which Laravel treats as PUT. */
    public function update(UpdateBlogRequest $request, Blog $blog): JsonResponse
    {
        try {
            $blog = $this->service->update(
                $blog,
                $request->blogData(),
                $request->file('featured_image'),
                $request->boolean('remove_featured_image'),
            );
        } catch (RuntimeException $e) {
            return $this->saveFailed($e);
        }

        return $this->success($this->toArray($blog, $request), 'Blog updated successfully.');
    }

    public function destroy(Blog $blog): JsonResponse
    {
        $this->service->delete($blog);

        return $this->success(null, 'Blog deleted successfully.');
    }

    public function publish(Request $request, Blog $blog): JsonResponse
    {
        return $this->success($this->toArray($this->service->publish($blog), $request), 'Blog published successfully.');
    }

    public function unpublish(Request $request, Blog $blog): JsonResponse
    {
        return $this->success($this->toArray($this->service->unpublish($blog), $request), 'Blog unpublished successfully.');
    }

    private function toArray(Blog $blog, Request $request): array
    {
        return (new BlogResource($blog->loadMissing('category')))->resolve($request);
    }

    /** Details go to the log only; the response never contains file paths or internal messages. */
    private function saveFailed(RuntimeException $e): JsonResponse
    {
        Log::error('Blog save failed.', ['error' => $e->getMessage()]);

        return response()->json([
            'success' => false,
            'message' => 'The post could not be saved. Please try again.',
        ], 500);
    }
}
