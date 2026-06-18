<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\ProductionRun;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bakery production: recipes (finished product + ingredients) and production runs
 * that consume ingredients and produce finished goods.
 */
class BakeryProductionController extends AppBaseController
{
    // ── Recipes ────────────────────────────────────────────────────────────────

    public function recipes(Request $request): JsonResponse
    {
        $rows = Recipe::query()->with(['product:id,name', 'items.product:id,name'])
            ->orderBy('name')->get();
        return $this->sendResponse($rows, 'Recipes retrieved.');
    }

    public function storeRecipe(Request $request): JsonResponse
    {
        return $this->persistRecipe(new Recipe(), $request, 'Recipe created.');
    }

    public function updateRecipe(Request $request, Recipe $recipe): JsonResponse
    {
        return $this->persistRecipe($recipe, $request, 'Recipe updated.');
    }

    public function destroyRecipe(Recipe $recipe): JsonResponse
    {
        RecipeItem::query()->where('recipe_id', $recipe->id)->delete();
        $recipe->delete();
        return $this->sendSuccess('Recipe removed.');
    }

    private function persistRecipe(Recipe $recipe, Request $request, string $message): JsonResponse
    {
        $data = $request->validate([
            'product_id'         => 'required|integer',
            'name'               => 'required|string|max:150',
            'yield_qty'          => 'required|numeric|min:0.01',
            'status'             => 'nullable|boolean',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($recipe, $data) {
            $recipe->fill([
                'product_id' => $data['product_id'],
                'name'       => $data['name'],
                'yield_qty'  => $data['yield_qty'],
                'status'     => $data['status'] ?? true,
            ])->save();

            RecipeItem::query()->where('recipe_id', $recipe->id)->delete();
            foreach ($data['items'] as $it) {
                RecipeItem::create([
                    'recipe_id'  => $recipe->id,
                    'product_id' => $it['product_id'],
                    'quantity'   => $it['quantity'],
                ]);
            }
        });

        return $this->sendResponse($recipe->load('items.product:id,name'), $message);
    }

    // ── Production runs ──────────────────────────────────────────────────────────

    public function runs(Request $request): JsonResponse
    {
        $rows = ProductionRun::query()->with('recipe:id,name')
            ->orderByDesc('id')->paginate((int) $request->get('page_size', 25));
        return $this->sendResponse($rows, 'Production runs retrieved.');
    }

    public function storeRun(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipe_id'    => 'required|integer',
            'date'         => 'required|date',
            'batches'      => 'required|numeric|min:0.01',
            'wastage_qty'  => 'nullable|numeric|min:0',
            'note'         => 'nullable|string|max:191',
        ]);

        $recipe = Recipe::query()->with('items')->findOrFail($data['recipe_id']);
        $batches = (float) $data['batches'];

        // Consumed = each ingredient × yield × batches. Snapshot stored on the run.
        $consumed = $recipe->items->map(fn ($it) => [
            'product_id' => $it->product_id,
            'quantity'   => round((float) $it->quantity * (float) $recipe->yield_qty * $batches, 4),
        ])->values()->all();

        $produced = round((float) $recipe->yield_qty * $batches, 2);

        $run = ProductionRun::create([
            'recipe_id'    => $recipe->id,
            'date'         => $data['date'],
            'batches'      => $batches,
            'produced_qty' => $produced,
            'wastage_qty'  => $data['wastage_qty'] ?? 0,
            'consumed'     => $consumed,
            'status'       => 'completed',
            'note'         => $data['note'] ?? null,
            'created_by'   => $request->user()?->id,
        ]);

        return $this->sendResponse($run->load('recipe:id,name'), 'Production run recorded.');
    }
}
