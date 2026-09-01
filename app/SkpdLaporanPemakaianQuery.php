<?php

namespace App;

use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\Loket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class SkpdLaporanPemakaianQuery
{
    public function __construct(
        private readonly int $month,
        private readonly int $year,
        private readonly ?int $loketId,
    ) {}

    /**
     * @return array{month: int, year: int, loket: int|null}
     */
    public function filters(): array
    {
        return [
            'month' => $this->month,
            'year' => $this->year,
            'loket' => $this->loketId,
        ];
    }

    public function periodLabel(): string
    {
        return self::monthName($this->month).' '.$this->year;
    }

    public function selectedLoketName(): ?string
    {
        if ($this->loketId === null) {
            return null;
        }

        $name = Loket::query()->whereKey($this->loketId)->value('name');

        return is_string($name) ? $name : null;
    }

    public function pdfFilename(): string
    {
        return $this->filenamePrefix().'.pdf';
    }

    public function excelFilename(): string
    {
        return $this->filenamePrefix().'.xlsx';
    }

    /**
     * @return Builder<Bap>
     */
    public function detailQuery(): Builder
    {
        return $this->completedBapQuery()
            ->with('loket:id,name')
            ->withCount('cancellations')
            ->orderByDesc('service_date')
            ->orderByDesc('id');
    }

    /**
     * @return array{total_baps: int, total_usage: int, total_online: int, total_cancellations: int}
     */
    public function summary(): array
    {
        $query = $this->completedBapQuery();
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
     * @return list<array{loket_id: int, loket: string, total_baps: int, total_usage: int, total_online: int, total_cancellations: int}>
     */
    public function loketRecaps(): array
    {
        $query = $this->completedBapQuery();
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
     * @return Builder<Bap>
     */
    private function completedBapQuery(): Builder
    {
        $periodStart = CarbonImmutable::create($this->year, $this->month, 1)->startOfDay();
        $periodEnd = $periodStart->endOfMonth();
        $query = Bap::query()
            ->where('status', BapStatus::Completed->value)
            ->whereDate('service_date', '>=', $periodStart->toDateString())
            ->whereDate('service_date', '<=', $periodEnd->toDateString());

        if ($this->loketId !== null) {
            $query->where('loket_id', $this->loketId);
        }

        return $query;
    }

    private function filenamePrefix(): string
    {
        return 'laporan-pemakaian-skpd-'.strtolower(self::monthName($this->month)).'-'.$this->year;
    }

    private static function monthName(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month];
    }
}
