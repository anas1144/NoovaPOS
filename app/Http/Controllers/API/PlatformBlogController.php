<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Super-admin blog management.
 */
class PlatformBlogController extends AppBaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse(
            BlogPost::query()->orderByDesc('id')->get(),
            'Blog posts retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePost($request);
        $post = BlogPost::create($data + ['slug' => $this->uniqueSlug($data['title'])]);

        return $this->sendResponse($post, 'Blog post created successfully.');
    }

    public function update(Request $request, BlogPost $blogPost): JsonResponse
    {
        $blogPost->update($this->validatePost($request));

        return $this->sendResponse($blogPost->refresh(), 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return $this->sendSuccess('Blog post deleted successfully.');
    }

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title'           => 'required|string|max:191',
            'cover_image'     => 'nullable|string|max:500',
            'excerpt'         => 'nullable|string|max:1000',
            'content'         => 'nullable|string',
            'author'          => 'nullable|string|max:120',
            'seo_title'       => 'nullable|string|max:191',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords'    => 'nullable|string|max:255',
            'status'          => 'nullable|boolean',
            'published_at'    => 'nullable|date',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 1;
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
