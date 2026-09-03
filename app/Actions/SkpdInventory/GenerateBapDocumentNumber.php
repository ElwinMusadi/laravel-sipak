<?php

namespace App\Actions\SkpdInventory;

use App\Models\Loket;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class GenerateBapDocumentNumber
{
    public function handle(Loket $loket, CarbonInterface $createdAt): string
    {
        return sprintf(
            'PB/%s/%s',
            $this->loketCode($loket),
            $createdAt->format('d/m/Y'),
        );
    }

    private function loketCode(Loket $loket): string
    {
        return match ($loket->code) {
            'MPP' => 'MPP',
            'SAMSAT-KANTOR' => 'LOKET',
            default => Str::upper((string) preg_replace('/[^\pL\pN]/u', '', $loket->name)),
        };
    }
}
