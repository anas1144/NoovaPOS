<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\CrmLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CRMAPIController extends AppBaseController
{
    public function leads(Request $request): JsonResponse
    {
        $query = CrmLead::query()->with([
            'assignee:id,first_name,last_name,email',
            'customer:id,name,phone',
        ]);
        foreach (['stage', 'source', 'assigned_to'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('search')) {
            $s = trim((string) $request->get('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%");
            });
        }
        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'Leads retrieved successfully.'
        );
    }

    public function storeLead(Request $request): JsonResponse
    {
        $input = $this->validateLead($request);
        return $this->sendResponse(CrmLead::create($input), 'Lead created successfully.');
    }

    public function updateLead(Request $request, CrmLead $lead): JsonResponse
    {
        $input = $this->validateLead($request);
        $lead->update($input);
        return $this->sendResponse($lead->refresh(), 'Lead updated successfully.');
    }

    public function changeStage(Request $request, CrmLead $lead): JsonResponse
    {
        $input = $request->validate([
            'stage' => ['required', Rule::in(CrmLead::PIPELINE_STAGES)],
        ]);
        $patch = ['stage' => $input['stage']];
        if (in_array($input['stage'], [CrmLead::STAGE_WON, CrmLead::STAGE_LOST], true)) {
            $patch['closed_at'] = now()->toDateString();
        } else {
            $patch['closed_at'] = null;
        }
        $lead->update($patch);
        return $this->sendResponse($lead->refresh(), 'Lead stage updated.');
    }

    public function destroyLead(CrmLead $lead): JsonResponse
    {
        $lead->delete();
        return $this->sendSuccess('Lead deleted successfully.');
    }

    public function pipelineStats(Request $request): JsonResponse
    {
        $rows = CrmLead::query()
            ->select('stage', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(estimated_value), 0) as total'))
            ->groupBy('stage')
            ->get();

        $byStage = collect(CrmLead::PIPELINE_STAGES)->mapWithKeys(function ($s) use ($rows) {
            $row = $rows->firstWhere('stage', $s);
            return [
                $s => [
                    'count' => $row ? (int) $row->cnt : 0,
                    'total_value' => $row ? (float) $row->total : 0,
                ],
            ];
        });

        $open = $byStage->except([CrmLead::STAGE_WON, CrmLead::STAGE_LOST])->sum('count');
        $totalPipeline = $byStage->except([CrmLead::STAGE_WON, CrmLead::STAGE_LOST])->sum('total_value');
        $won = $byStage[CrmLead::STAGE_WON]['count'] ?? 0;
        $lost = $byStage[CrmLead::STAGE_LOST]['count'] ?? 0;
        $closed = $won + $lost;
        $winRate = $closed > 0 ? round(($won / $closed) * 100, 1) : 0;

        return $this->sendResponse([
            'by_stage' => $byStage,
            'totals' => [
                'open_leads' => $open,
                'pipeline_value' => $totalPipeline,
                'won' => $won,
                'lost' => $lost,
                'win_rate' => $winRate,
            ],
        ], 'CRM pipeline stats retrieved.');
    }

    private function validateLead(Request $request): array
    {
        return $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:60',
            'stage' => ['nullable', Rule::in(CrmLead::PIPELINE_STAGES)],
            'estimated_value' => 'nullable|numeric|min:0',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
    }
}
