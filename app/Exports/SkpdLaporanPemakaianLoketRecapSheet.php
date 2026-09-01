<?php

namespace App\Exports;

use App\SkpdLaporanPemakaianQuery;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class SkpdLaporanPemakaianLoketRecapSheet implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(private readonly SkpdLaporanPemakaianQuery $report) {}

    /**
     * @return array<int, array<int, int|string|null>>
     */
    public function array(): array
    {
        $rows = [
            ['Laporan Sistem — Rekap Pemakaian per Loket'],
            ['Periode', $this->report->periodLabel()],
            ['Loket', $this->report->selectedLoketName() ?? 'Semua Loket'],
            [null],
            ['Loket', 'Total BAP', 'SKPD Terpakai', 'Online', 'Batal/Rusak'],
        ];

        foreach ($this->report->loketRecaps() as $recap) {
            $rows[] = [
                $recap['loket'],
                $recap['total_baps'],
                $recap['total_usage'],
                $recap['total_online'],
                $recap['total_cancellations'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 14,
            'C' => 18,
            'D' => 14,
            'E' => 18,
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A6');
                $sheet->setAutoFilter('A5:E'.$sheet->getHighestRow());
            },
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            5 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F3E8C3']]],
        ];
    }

    public function title(): string
    {
        return 'Rekap Loket';
    }
}
