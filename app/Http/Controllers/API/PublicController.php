<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DemoRequest;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /**
     * GET /api/public/countries
     * Full country list (id, name, ISO short_code) for country dropdowns.
     */
    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get(['id', 'name', 'short_code']);

        return response()->json(['data' => $countries]);
    }

    /**
     * GET /api/public/cms/menu
     * Pages flagged to appear in the marketing top menu.
     */
    public function cmsMenu(): JsonResponse
    {
        $pages = \App\Models\CmsPage::query()
            ->where('status', true)
            ->where('show_in_menu', true)
            ->orderBy('menu_order')
            ->get(['slug', 'title', 'type', 'shop_type']);

        return response()->json(['data' => $pages]);
    }

    /**
     * GET /api/public/cms/page/{slug}?country=PK
     * A page with sections filtered by the visitor's country.
     */
    public function cmsPage(Request $request, string $slug): JsonResponse
    {
        $page = \App\Models\CmsPage::query()
            ->where('slug', $slug)->where('status', true)->first();

        if (! $page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $country = $request->get('country');
        $sections = $page->sections->filter(fn ($s) => $s->isVisibleFor($country))->values();

        return response()->json([
            'data' => [
                'page'     => $page->only(['slug', 'title', 'type', 'shop_type', 'seo_title', 'seo_description', 'seo_keywords']),
                'sections' => $sections,
            ],
        ]);
    }

    /**
     * GET /api/public/blog  and  /api/public/blog/{slug}
     */
    public function blogList(): JsonResponse
    {
        $posts = \App\Models\BlogPost::query()
            ->where('status', true)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->get(['slug', 'title', 'excerpt', 'cover_image', 'author', 'published_at']);

        return response()->json(['data' => $posts]);
    }

    public function blogPost(string $slug): JsonResponse
    {
        $post = \App\Models\BlogPost::query()
            ->where('slug', $slug)->where('status', true)->first();

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json(['data' => $post]);
    }

    /**
     * GET /api/public/shop-types
     * Enabled shop/business types for the store-create dropdown.
     */
    public function shopTypes(): JsonResponse
    {
        $types = \App\Models\ShopType::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['key', 'label']);

        return response()->json(['data' => $types]);
    }

    /**
     * GET /api/public/currencies
     * Distinct system currency codes for currency dropdowns.
     */
    public function currencies(): JsonResponse
    {
        $currencies = \App\Models\Currency::withoutGlobalScope('tenant')
            ->select('code', 'name')
            ->get()
            ->unique('code')
            ->sortBy('code')
            ->values();

        return response()->json(['data' => $currencies]);
    }

    /**
     * GET /api/public/plans
     * Returns publicly visible plans ordered by sort_order.
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get([
                'id', 'name', 'slug', 'description',
                'price', 'price_pkr', 'price_yearly', 'price_yearly_pkr',
                'per_shop_price', 'per_shop_price_pkr',
                'trial_days', 'billing_cycle',
                'max_stores', 'max_shops', 'max_registers', 'max_users', 'max_products',
                'features',
                'is_featured', 'is_contact_sales', 'is_custom',
                'allowed_countries', 'sort_order',
            ]);

        return response()->json(['data' => $plans]);
    }

    /**
     * POST /api/public/demo-request
     * Stores a new demo/sales request from the landing page.
     */
    public function demoRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:5',
            'message'       => 'nullable|string|max:2000',
        ]);

        $demo = DemoRequest::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'status'     => 'new',
        ]);

        // TODO: Fire DemoRequestReceived event to notify super admin via email/notification

        return response()->json([
            'success' => true,
            'message' => 'Thanks! Our team will reach out within 24 hours.',
            'id'      => $demo->id,
        ], 201);
    }

    /**
     * GET /api/public/demo-requests  (super admin only — via Sanctum)
     * List all demo requests for super admin dashboard.
     */
    public function demoRequestList(Request $request): JsonResponse
    {
        $query = DemoRequest::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('business_name', 'like', "%{$s}%");
            });
        }

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    /**
     * PATCH /api/public/demo-requests/{id}  (super admin only)
     */
    public function demoRequestUpdate(Request $request, int $id): JsonResponse
    {
        $demo = DemoRequest::findOrFail($id);
        $validated = $request->validate([
            'status'       => 'sometimes|in:new,contacted,converted,closed',
            'notes'        => 'sometimes|nullable|string|max:5000',
            'contacted_at' => 'sometimes|nullable|date',
            'assigned_to'  => 'sometimes|nullable|integer',
        ]);
        $demo->update($validated);
        return response()->json(['success' => true, 'data' => $demo]);
    }
}
