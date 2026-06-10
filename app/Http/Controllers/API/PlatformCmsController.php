<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\CmsPage;
use App\Models\CmsSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Super-admin CMS: marketing pages + their sections.
 */
class PlatformCmsController extends AppBaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse(
            CmsPage::query()->orderBy('menu_order')->orderBy('title')->get(),
            'CMS pages retrieved successfully.'
        );
    }

    public function show(CmsPage $cmsPage): JsonResponse
    {
        return $this->sendResponse($cmsPage->load('sections'), 'CMS page retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePage($request);
        $page = CmsPage::create($data + ['slug' => $this->uniqueSlug($data['title'])]);
        $this->syncSections($page, $request->input('sections'));

        return $this->sendResponse($page->load('sections'), 'CMS page created successfully.');
    }

    public function update(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $data = $this->validatePage($request);
        $cmsPage->update($data);
        $this->syncSections($cmsPage, $request->input('sections'));

        return $this->sendResponse($cmsPage->load('sections'), 'CMS page updated successfully.');
    }

    public function destroy(CmsPage $cmsPage): JsonResponse
    {
        if ($cmsPage->is_system) {
            return $this->sendError('System pages cannot be deleted.', 422);
        }
        CmsSection::where('page_id', $cmsPage->id)->delete();
        $cmsPage->delete();

        return $this->sendSuccess('CMS page deleted successfully.');
    }

    private function validatePage(Request $request): array
    {
        return $request->validate([
            'title'           => 'required|string|max:191',
            'type'            => 'nullable|in:landing,shop_type,fbr,custom',
            'shop_type'       => 'nullable|string|max:60',
            'show_in_menu'    => 'nullable|boolean',
            'menu_order'      => 'nullable|integer',
            'seo_title'       => 'nullable|string|max:191',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords'    => 'nullable|string|max:255',
            'status'          => 'nullable|boolean',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 1;
        while (CmsPage::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function syncSections(CmsPage $page, $sections): void
    {
        if (is_string($sections)) {
            $sections = json_decode($sections, true);
        }
        if (! is_array($sections)) {
            return;
        }

        DB::transaction(function () use ($page, $sections) {
            CmsSection::where('page_id', $page->id)->delete();
            $sort = 0;
            foreach ($sections as $s) {
                CmsSection::create([
                    'page_id'           => $page->id,
                    'key'               => $s['key'] ?? null,
                    'type'              => $s['type'] ?? 'html',
                    'content'           => is_array($s['content'] ?? null) ? json_encode($s['content']) : ($s['content'] ?? null),
                    'sort_order'        => $sort++,
                    'visible_global'    => $s['visible_global'] ?? true,
                    'visible_countries' => $s['visible_countries'] ?? null,
                    'status'            => $s['status'] ?? true,
                ]);
            }
        });
    }
}
