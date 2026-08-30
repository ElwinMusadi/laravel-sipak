<?php

namespace App\Actions\SkpdInventory;

use App\BapCancellationReason;
use App\BapStatus;
use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordBapCancellation
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(
        User $actor,
        Bap $bap,
        int $numerator,
        BapCancellationReason $reason,
        ?string $description = null,
    ): BapCancellation {
        return DB::transaction(function () use ($actor, $bap, $numerator, $reason, $description): BapCancellation {
            $this->lockInventory();

            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($bap->id);

            if ($lockedBap->status !== BapStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Batal atau rusak hanya dapat dicatat saat BAP masih draft.',
                ]);
            }

            if ($actor->loket_id !== $lockedBap->loket_id) {
                throw ValidationException::withMessages([
                    'loket_id' => 'Batal atau rusak hanya dapat dicatat oleh Petugas Loket pemilik BAP.',
                ]);
            }

            if ($numerator < $lockedBap->numerator_start || $numerator > $lockedBap->numerator_end) {
                throw ValidationException::withMessages([
                    'numerator' => 'Nomeratur batal atau rusak harus berada di dalam range BAP.',
                ]);
            }

            if (BapCancellation::query()->where('numerator', $numerator)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'numerator' => 'Nomeratur batal atau rusak sudah pernah dicatat dan tidak dapat digunakan ulang.',
                ]);
            }

            $cancellation = BapCancellation::create([
                'bap_id' => $lockedBap->id,
                'numerator' => $numerator,
                'reason' => $reason,
                'description' => filled($description) ? trim($description) : null,
                'created_by' => $actor->id,
            ]);

            $this->audit->handle($actor, $lockedBap, 'bap_cancellation.recorded', null, [
                'numerator' => $cancellation->numerator,
                'reason' => $cancellation->reason->value,
                'description' => $cancellation->description,
            ]);

            return $cancellation;
        }, attempts: 3);
    }

    private function lockInventory(): void
    {
        $lock = DB::table('skpd_inventory_locks')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        if ($lock === null) {
            throw new \LogicException('Kunci transaksi inventaris SKPD tidak tersedia.');
        }
    }
}
