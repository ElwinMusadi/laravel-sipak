<?php

namespace App\Http\Controllers;

use App\BapStatus;
use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\Loket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SkpdLaporanPemakaianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->actor($request);

        Gate::authorize('view-laporan-pemakaian');

        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,9999'],
            'loket' => ['nullable', 'integer', 'exists:lokets,id'],
        ]);
        $month = isset($filters['month']) ? (int) $filters['month'] : now()->month;
        $year = isset($filters['year']) ? (int) $filters['year'] : now()->year;
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->endOfMonth();
        $loketId = isset($filters['loket']) ? (int) $filters['loket'] : null;

        $query = $this->completedBapQuery(
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
            $loketId,
        );

        return Inertia::render('laporan-pemakaian/index', [
            'baps' => (clone $query)
                ->with('loket:id,name')
                ->withCount('cancellations')
                ->orderByDesc('service_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Bap $bap): array => $this->bapData($bap)),
            'filters' => [
                'month' => $month,
                'year' => $year,
                'loket' => $loketId,
            ],
            'lokets' => Loket::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Loket $loket): array => [
                    'id' => $loket->id,
                    'name' => $loket->name,
                ])
                ->all(),
            'summary' => $this->summaryData(clone $query),
            'loket_recaps' => $this->loketRecapData(clone $query),
        ]);
    }

    /**
     * @return Builder<Bap>
     */
    private function completedBapQuery(
        string $periodStart,
        string $periodEnd,
        ?int $loketId,
    ): Builder {
        $query = Bap::query()
            ->where('status', BapStatus::Completed->value)
            ->whereDate('service_date', '>=', $periodStart)
            ->whereDate('service_date', '<=', $periodEnd);

        if ($loketId !== null) {
            $query->where('loket_id', $loketId);
        }

        return $query;
    }

    /**
     * @param  Builder<Bap>  $query
     * @return array{total_baps: int, total_usage: int, total_online: int, total_cancellations: int}
     */
    private function summaryData(Builder $query): array
    {
        $summary = (clone $query)
            ->toBase()
            ->selectRaw('count(*) as total_baps')
            ->selectRaw('coalesce(sum(total_usage), 0) as total_usage')
            ->selectRaw('coalesce(sum(online_usage_count), 0) as total_online')
            ->first();
        $totalCancellations = BapCancellation::query()
            ->whereIn('bap_id', (clone $query)->select('id'))
            ->count();

        return [
            'total_baps' => (int) ($summary->total_baps ?? 0),
            'total_usage' => (int) ($summary->total_usage ?? 0),
            'total_online' => (int) ($summary->total_online ?? 0),
            'total_cancellations' => $totalCancellations,
        ];
    }

    /**
     * @param  Builder<Bap>  $query
     * @return list<array{loket_id: int, loket: string, total_baps: int, total_usage: int, total_online: int, total_cancellations: int}>
     */
    private function loketRecapData(Builder $query): array
    {
        $cancellationsByLoket = BapCancellation::query()
            ->toBase()
            ->join('baps', 'baps.id', '=', 'bap_cancellations.bap_id')
            ->whereIn('bap_cancellations.bap_id', (clone $query)->select('id'))
            ->select('baps.loket_id')
            ->selectRaw('count(*) as total_cancellations')
            ->groupBy('baps.loket_id')
            ->pluck('total_cancellations', 'loket_id');

        return array_values((clone $query)
            ->toBase()
            ->join('lokets', 'lokets.id', '=', 'baps.loket_id')
            ->select('baps.loket_id', 'lokets.name as loket')
            ->selectRaw('count(*) as total_baps')
            ->selectRaw('coalesce(sum(baps.total_usage), 0) as total_usage')
            ->selectRaw('coalesce(sum(baps.online_usage_count), 0) as total_online')
            ->groupBy('baps.loket_id', 'lokets.name')
            ->orderBy('lokets.name')
            ->get()
            ->map(fn (object $recap): array => [
                'loket_id' => (int) $recap->loket_id,
                'loket' => (string) $recap->loket,
                'total_baps' => (int) $recap->total_baps,
                'total_usage' => (int) $recap->total_usage,
                'total_online' => (int) $recap->total_online,
                'total_cancellations' => (int) ($cancellationsByLoket[$recap->loket_id] ?? 0),
            ])
            ->all());
    }

    /**
     * @return array{id: int, number: string, service_date: string, loket: string, numerator_start: int, numerator_end: int, total_usage: int, online_usage_count: int, cancellation_count: int}
     */
    private function bapData(Bap $bap): array
    {
        return [
            'id' => $bap->id,
            'number' => '#'.$bap->id,
            'service_date' => $bap->service_date->toDateString(),
            'loket' => $bap->loket->name,
            'numerator_start' => $bap->numerator_start,
            'numerator_end' => $bap->numerator_end,
            'total_usage' => $bap->total_usage,
            'online_usage_count' => $bap->online_usage_count,
            'cancellation_count' => $bap->cancellations_count,
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
