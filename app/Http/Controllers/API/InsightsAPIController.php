<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight, statistical "AI" insights:
 *  - Sales forecast: rolling moving average over last 30 days, projected next N days
 *  - Reorder suggestions: consumption velocity (avg daily out) vs current stock
 *  - Customer buying trends: top customers by revenue & frequency
 *
 * No external ML libs — pure SQL aggregates that work in the existing app.
 */
class InsightsAPIController extends AppBaseController
{
    public function salesForecast(Request $request): JsonResponse
    {
        $days = max(7, min(90, (int) $request->get('history_days', 30)));
        $forecast = max(7, min(60, (int) $request->get('forecast_days', 14)));

        $tenantId = currentTenantId();

        $start = Carbon::now()->subDays($days)->toDateString();

        $rows = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $start)
            ->selectRaw('DATE(date) as d, COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as total')
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('d')
            ->get();

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r->d] = ['count' => (int) $r->cnt, 'total' => (float) $r->total];
        }

        // Fill in missing days with zero
        $cursor = Carbon::parse($start);
        $end = Carbon::now()->startOfDay();
        $history = [];
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $history[] = [
                'date' => $d,
                'sales' => $byDay[$d]['total'] ?? 0,
                'orders' => $byDay[$d]['count'] ?? 0,
            ];
            $cursor->addDay();
        }

        $totals = collect($history)->pluck('sales');
        $movingAverage = $totals->count() > 0
            ? round($totals->sum() / max(1, $totals->count()), 2)
            : 0;

        // 7-day weighted MA: more recent days weigh higher.
        $recent = $totals->slice(-7)->values();
        $weights = [1, 1.2, 1.4, 1.6, 1.8, 2.0, 2.2];
        $weightedSum = 0;
        $weightTotal = 0;
        $recentArr = $recent->all();
        foreach ($recentArr as $i => $val) {
            $w = $weights[$i] ?? 1;
            $weightedSum += $val * $w;
            $weightTotal += $w;
        }
        $weightedMA = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : $movingAverage;

        // Trend slope (simple least-squares over last 14 days for direction)
        $window = $totals->slice(-14)->values()->all();
        $slope = 0;
        $n = count($window);
        if ($n >= 2) {
            $xMean = ($n - 1) / 2;
            $yMean = array_sum($window) / $n;
            $num = 0;
            $den = 0;
            foreach ($window as $i => $y) {
                $num += ($i - $xMean) * ($y - $yMean);
                $den += ($i - $xMean) ** 2;
            }
            $slope = $den > 0 ? round($num / $den, 4) : 0;
        }

        // Forecast: weighted MA + (slope * day offset), floor at 0
        $forecastSeries = [];
        $forecastTotal = 0;
        $startForecast = Carbon::now()->addDay()->startOfDay();
        for ($i = 0; $i < $forecast; $i++) {
            $val = max(0, round($weightedMA + $slope * ($i + 1), 2));
            $forecastSeries[] = [
                'date' => $startForecast->copy()->addDays($i)->toDateString(),
                'forecast' => $val,
            ];
            $forecastTotal += $val;
        }

        return $this->sendResponse([
            'history' => $history,
            'forecast' => $forecastSeries,
            'metrics' => [
                'avg_daily_sales' => $movingAverage,
                'weighted_avg_recent' => $weightedMA,
                'trend_slope' => $slope,
                'forecast_total' => round($forecastTotal, 2),
            ],
        ], 'Sales forecast retrieved successfully.');
    }

    public function reorderSuggestions(Request $request): JsonResponse
    {
        $lookback = max(7, min(180, (int) $request->get('days', 30)));
        $coverDays = max(3, min(60, (int) $request->get('cover_days', 14)));

        $tenantId = currentTenantId();
        $start = Carbon::now()->subDays($lookback)->toDateString();

        // Aggregate outflow per product from stock_movements
        $movements = DB::table('stock_movements')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', $start)
            ->where('direction', 'out')
            ->select('product_id', DB::raw('COALESCE(SUM(quantity), 0) as out_qty'))
            ->groupBy('product_id')
            ->pluck('out_qty', 'product_id');

        // Current stock per product (from manage_stocks if exists, else products.stock_quantity)
        $currentStock = DB::table('manage_stocks')
            ->where('tenant_id', $tenantId)
            ->select('product_id', DB::raw('COALESCE(SUM(quantity), 0) as stock'))
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $productRows = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->select(
                'id',
                'name',
                'code',
                DB::raw('COALESCE(quantity_limit, 0) as min_qty'),
                DB::raw('COALESCE(stock_quantity, 0) as fallback_stock')
            )
            ->get();

        $suggestions = $productRows->map(function ($p) use ($movements, $currentStock, $lookback, $coverDays) {
            $out = (float) ($movements[$p->id] ?? 0);
            $velocity = round($out / max(1, $lookback), 3);
            $stock = (float) ($currentStock[$p->id] ?? $p->fallback_stock ?? 0);
            $daysOfCover = $velocity > 0 ? round($stock / $velocity, 1) : null;
            $suggestedQty = max(0, round($velocity * $coverDays - $stock, 2));
            return [
                'product_id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'velocity_per_day' => $velocity,
                'current_stock' => $stock,
                'min_qty' => (float) $p->min_qty,
                'days_of_cover' => $daysOfCover,
                'suggested_reorder_qty' => $suggestedQty,
                'urgency' => $this->urgency($daysOfCover, $stock, $p->min_qty),
            ];
        })
            ->filter(fn($r) => $r['velocity_per_day'] > 0 && $r['suggested_reorder_qty'] > 0)
            ->sortByDesc(function ($r) {
                return match ($r['urgency']) {
                    'critical' => 3,
                    'high' => 2,
                    'medium' => 1,
                    default => 0,
                };
            })
            ->values();

        return $this->sendResponse([
            'lookback_days' => $lookback,
            'target_cover_days' => $coverDays,
            'rows' => $suggestions,
        ], 'Reorder suggestions retrieved successfully.');
    }

    public function customerTrends(Request $request): JsonResponse
    {
        $days = max(7, min(365, (int) $request->get('days', 90)));
        $tenantId = currentTenantId();
        $start = Carbon::now()->subDays($days)->toDateString();

        $rows = DB::table('sales')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.tenant_id', $tenantId)
            ->whereDate('sales.date', '>=', $start)
            ->select(
                'sales.customer_id',
                DB::raw('COALESCE(customers.name, "Walk-in") as customer_name'),
                DB::raw('COUNT(sales.id) as orders'),
                DB::raw('COALESCE(SUM(sales.grand_total), 0) as revenue'),
                DB::raw('COALESCE(AVG(sales.grand_total), 0) as avg_ticket')
            )
            ->groupBy('sales.customer_id', 'customer_name')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'customer_id' => $r->customer_id,
                'name' => $r->customer_name,
                'orders' => (int) $r->orders,
                'revenue' => (float) $r->revenue,
                'avg_ticket' => round((float) $r->avg_ticket, 2),
            ]);

        return $this->sendResponse(['rows' => $rows, 'days' => $days], 'Customer trends retrieved successfully.');
    }

    private function urgency(?float $daysOfCover, float $stock, float $minQty): string
    {
        if ($daysOfCover === null) {
            return 'none';
        }
        if ($stock <= 0 || $daysOfCover < 3) {
            return 'critical';
        }
        if ($daysOfCover < 7 || ($minQty > 0 && $stock <= $minQty)) {
            return 'high';
        }
        if ($daysOfCover < 14) {
            return 'medium';
        }
        return 'low';
    }
}
