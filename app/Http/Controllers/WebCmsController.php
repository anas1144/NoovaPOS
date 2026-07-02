<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\CmsPage;
use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * Server-rendered marketing CMS pages + blog (central domain).
 */
class WebCmsController extends Controller
{
    private function menu()
    {
        return CmsPage::query()
            ->where('status', true)
            ->where('show_in_menu', true)
            ->orderBy('menu_order')
            ->get(['slug', 'title', 'type', 'shop_type']);
    }

    public function page(Request $request, string $slug)
    {
        $page = CmsPage::query()->where('slug', $slug)->where('status', true)->first();
        if (! $page) {
            abort(404);
        }

        $country = $request->get('country') ?: $request->cookie('visitor_country');
        $sections = $page->sections->filter(fn ($s) => $s->isVisibleFor($country))->values();

        // Show the plan(s) available for this business type. Guard against the
        // shop_type column not yet existing (migration not run) so the page
        // never 500s — it just shows no plans until migrated.
        $plans = collect();
        if ($page->type === 'shop_type' && $page->shop_type
            && \Illuminate\Support\Facades\Schema::hasColumn('plans', 'shop_type')) {
            $plans = Plan::query()
                ->where('shop_type', $page->shop_type)
                ->where('status', true)
                ->orderBy('price')
                ->get();
        }

        return view('cms.page', [
            'page'     => $page,
            'sections' => $sections,
            'plans'    => $plans,
            'menu'     => $this->menu(),
        ]);
    }

    public function blogList()
    {
        return view('cms.blog-list', [
            'posts' => BlogPost::query()->where('status', true)
                ->whereNotNull('published_at')->orderByDesc('published_at')->get(),
            'menu'  => $this->menu(),
        ]);
    }

    public function blogPost(string $slug)
    {
        $post = BlogPost::query()->where('slug', $slug)->where('status', true)->first();
        if (! $post) {
            abort(404);
        }

        return view('cms.blog-post', [
            'post' => $post,
            'menu' => $this->menu(),
        ]);
    }
}
