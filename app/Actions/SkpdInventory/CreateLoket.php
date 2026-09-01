<?php

namespace App\Actions\SkpdInventory;

use App\Models\Loket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLoket
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, string $code, string $name, ?string $description): Loket
    {
        $code = strtoupper(trim($code));
        $name = trim($name);
        $description = filled($description) ? trim($description) : null;

        return DB::transaction(function () use ($actor, $code, $name, $description): Loket {
            if (Loket::query()->where('code', $code)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Kode Loket sudah digunakan.',
                ]);
            }

            $loket = Loket::create([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'is_active' => true,
            ]);

            $this->audit->handle($actor, $loket, 'loket.created', null, $this->values($loket));

            return $loket;
        }, attempts: 3);
    }

    /**
     * @return array{code: string, name: string, description: string|null, is_active: bool}
     */
    private function values(Loket $loket): array
    {
        return [
            'code' => $loket->code,
            'name' => $loket->name,
            'description' => $loket->description,
            'is_active' => $loket->is_active,
        ];
    }
}
