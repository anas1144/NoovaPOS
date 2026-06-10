<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Services\FeatureService;
use App\Support\FeatureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin global feature toggles (apply to all tenants).
 */
class PlatformFeatureController extends AppBaseController
{
    public function __construct(private readonly FeatureService $features)
    {
    }

    public function index(): JsonResponse
    {
        return $this->sendResponse($this->features->globalStates(), 'Global features retrieved.');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'features'            => 'required|array',
            'features.*.key'      => 'required|string',
            'features.*.enabled'  => 'required|boolean',
        ]);

        foreach ($data['features'] as $f) {
            if (FeatureCatalog::isValid($f['key'])) {
                $this->features->setGlobal($f['key'], (bool) $f['enabled']);
            }
        }

        return $this->sendResponse($this->features->globalStates(), 'Global features updated.');
    }
}
