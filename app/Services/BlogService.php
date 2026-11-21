<?php

namespace App\Services;

use App\Models\Blog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BlogService
{
    private const DISK = 'public';
    private const DIRECTORY = 'blogs';

    public function create(array $data, ?UploadedFile $image = null): Blog
    {
        $newPath = $image ? $this->storeImage($image) : null;

        try {
            $blog = DB::transaction(function () use ($data, $newPath) {
                $data = $this->applyPublishRules($data);
                $data['featured_image'] = $newPath;

                return Blog::create($data);
            });
        } catch (Throwable $e) {
            $this->deleteFile($newPath); // the database write failed: do not leave an orphan file behind
            throw $e;
        }

        return $blog->load('category');
    }

    /**
     * A new upload wins over $removeImage. With neither, the current image is kept.
     * The old file is deleted only AFTER the database change has been saved.
     */
    public function update(Blog $blog, array $data, ?UploadedFile $image = null, bool $removeImage = false): Blog
    {
        $oldPath = $blog->featured_image;
        $newPath = $image ? $this->storeImage($image) : null;

        try {
            DB::transaction(function () use ($blog, $data, $newPath, $removeImage) {
                $data = $this->applyPublishRules($data);

                if ($newPath !== null) {
                    $data['featured_image'] = $newPath;
                } elseif ($removeImage) {
                    $data['featured_image'] = null;
                }

                $blog->update($data);
            });
        } catch (Throwable $e) {
            $this->deleteFile($newPath); // keep the old image, drop the unused new upload
            throw $e;
        }

        if ($oldPath && $oldPath !== $blog->featured_image) {
            $this->deleteImageIfUnused($oldPath, $blog->id);
        }

        return $blog->refresh()->load('category');
    }

    public function delete(Blog $blog): void
    {
        $path = $blog->featured_image;
        $id = $blog->id;

        DB::transaction(fn() => $blog->delete());

        if ($path) {
            $this->deleteImageIfUnused($path, $id);
        }
    }

    /** Keeps an existing published_at (it may be a scheduled date); sets it to now only when empty. */
    public function publish(Blog $blog): Blog
    {
        $blog->status = Blog::STATUS_PUBLISHED;

        if ($blog->published_at === null) {
            $blog->published_at = now();
        }

        $blog->save();

        return $blog->load('category');
    }

    /** Back to draft. published_at is kept, so publishing again later keeps the original date. */
    public function unpublish(Blog $blog): Blog
    {
        $blog->status = Blog::STATUS_DRAFT;
        $blog->save();

        return $blog->load('category');
    }

    private function applyPublishRules(array $data): array
    {
        if (($data['status'] ?? null) === Blog::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function storeImage(UploadedFile $image): string
    {
        // store() makes a random file name and picks the extension from the real file type,
        // so the name the browser sent is never used.
        $path = $image->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new RuntimeException('The featured image could not be saved.');
        }

        return $path;
    }

    private function deleteImageIfUnused(string $path, int $exceptBlogId): void
    {
        $stillUsed = Blog::where('featured_image', $path)->where('id', '!=', $exceptBlogId)->exists();

        if (! $stillUsed) {
            $this->deleteFile($path);
        }
    }

    /** Only ever deletes files inside blogs/. A failed delete is logged and never breaks the request. */
    private function deleteFile(?string $path): void
    {
        if (! $path || ! str_starts_with($path, self::DIRECTORY . '/') || str_contains($path, '..')) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $e) {
            Log::warning('Could not delete blog image.', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }
}
