<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FBR Error Center — searchable/filterable error-code reference with occurrence
 * tracking.
 */
class FbrErrorController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = FbrErrorCode::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->get('search');
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('message', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            })
            ->when($request->boolean('occurred_only'), fn ($q) => $q->where('total_occurrences', '>', 0))
            ->orderByDesc('total_occurrences')
            ->orderBy('code')
            ->get();

        return $this->sendResponse($rows, 'FBR error codes retrieved.');
    }
}
