<?php

namespace App\Exports;

use App\SkpdLaporanPemakaianQuery;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class SkpdLaporanPemakaianExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly SkpdLaporanPemakaianQuery $report) {}

    /**
     * @return array<int, Export>
     */
    public function sheets(): array
    {
        return [
            new SkpdLaporanPemakaianRingkasanSheet($this->report),
            new SkpdLaporanPemakaianLoketRecapSheet($this->report),
            new SkpdLaporanPemakaianDetailBapSheet($this->report),
        ];
    }
}
