<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrBusiness;
use App\Models\FbrDiProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * CRUD for the FBR Digital Invoicing product/item catalog, plus an HS-code
 * lookup that proxies FBR's public reference API (so the catalog can be built
 * with valid FBR HS codes). Scoped per tenant via the model's tenant scope.
 */
class FbrDiProductController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = FbrDiProduct::query()
            ->when($request->filled('fbr_business_id'), fn ($q) => $q->where('fbr_business_id', $request->get('fbr_business_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->get('search'));
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('hs_code', 'like', "%{$s}%"));
            })
            ->orderBy('name')
            ->get();

        return $this->sendResponse($rows, 'FBR DI products retrieved.');
    }

    public function show(FbrDiProduct $fbrDiProduct): JsonResponse
    {
        return $this->sendResponse($fbrDiProduct, 'FBR DI product retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->persist(new FbrDiProduct(), $request, 'FBR DI product created.');
    }

    public function update(Request $request, FbrDiProduct $fbrDiProduct): JsonResponse
    {
        return $this->persist($fbrDiProduct, $request, 'FBR DI product updated.');
    }

    public function destroy(FbrDiProduct $fbrDiProduct): JsonResponse
    {
        $fbrDiProduct->delete();
        return $this->sendSuccess('FBR DI product removed.');
    }

    private function persist(FbrDiProduct $product, Request $request, string $message): JsonResponse
    {
        $data = $request->validate([
            'fbr_business_id'   => 'nullable|integer',
            'name'              => 'required|string|max:191',
            'hs_code'           => 'nullable|string|max:20',
            'uom'               => 'nullable|string|max:30',
            'rate_per_unit'     => 'nullable|numeric|min:0',
            'rate_of_sales_tax' => 'nullable|numeric|min:0|max:100',
            'sro_no'            => 'nullable|string|max:50',
            'sro_item_serial'   => 'nullable|string|max:50',
            'sale_type'         => 'nullable|string|max:100',
            'category'          => 'nullable|string|max:100',
            'status'            => 'nullable|boolean',
        ]);

        $product->fill($data)->save();

        return $this->sendResponse($product->fresh(), $message);
    }

    /**
     * GET /api/fbr-di/hs-codes?q=...&fbr_business_id=...
     * Proxy FBR's HS-code reference API and return a filtered, normalized list:
     * [{ hs_code, description }]. Resilient — returns [] (with a note) on failure
     * so the UI degrades to manual entry.
     */
    public function hsCodes(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $token = $this->fbrToken($request->get('fbr_business_id'));
        if (! $token) {
            return $this->sendResponse([], 'No FBR token configured on a business yet — enter the HS code manually.');
        }

        $url = env('FBR_DI_HSCODE_URL', 'https://gw.fbr.gov.pk/pdi/v1/itemdesccode');

        try {
            $resp = Http::withToken($token)->acceptJson()->timeout(15)->get($url);
            if (! $resp->successful()) {
                return $this->sendResponse([], 'FBR HS-code service unavailable — enter the HS code manually.');
            }

            $list = collect($resp->json() ?: [])
                ->map(function ($r) {
                    // FBR returns varying key casings across reference endpoints.
                    $code = $r['hS_CODE'] ?? $r['hsCode'] ?? $r['hs_code'] ?? $r['HS_CODE'] ?? null;
                    $desc = $r['description'] ?? $r['itemDesc'] ?? $r['desc'] ?? '';
                    return $code ? ['hs_code' => (string) $code, 'description' => (string) $desc] : null;
                })
                ->filter()
                ->when($q !== '', function ($c) use ($q) {
                    $needle = mb_strtolower($q);
                    return $c->filter(fn ($i) => str_contains(mb_strtolower($i['hs_code']), $needle)
                        || str_contains(mb_strtolower($i['description']), $needle));
                })
                ->take(50)
                ->values();

            return $this->sendResponse($list, 'HS codes retrieved.');
        } catch (\Throwable $e) {
            return $this->sendResponse([], 'FBR HS-code lookup failed — enter the HS code manually.');
        }
    }

    /** Pick a usable FBR token for the tenant (given business, else any). */
    private function fbrToken($businessId): ?string
    {
        $business = $businessId
            ? FbrBusiness::query()->find($businessId)
            : FbrBusiness::query()->orderByDesc('status')->first();

        if (! $business) {
            return null;
        }

        return ($business->environment === 'production')
            ? ($business->production_token ?: $business->sandbox_token)
            : ($business->sandbox_token ?: $business->production_token);
    }
}
