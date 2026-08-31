<?php

namespace App\Actions\SkpdVerification;

use App\Actions\SkpdInventory\RecordDomainAudit;
use App\BapStatus;
use App\Models\Bap;
use App\Models\BapClarificationRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenBapClarification
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, BapClarificationRequest $clarification): void
    {
        DB::transaction(function () use ($actor, $clarification): void {
            $lockedClarification = BapClarificationRequest::query()
                ->lockForUpdate()
                ->findOrFail($clarification->id);
            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($lockedClarification->bap_id);

            if ($actor->role !== UserRole::PetugasLoket || $actor->loket_id !== $lockedBap->loket_id) {
                throw ValidationException::withMessages([
                    'clarification' => 'Hanya Petugas Loket pemilik BAP yang dapat membuka klarifikasi ini.',
                ]);
            }

            if ($lockedBap->status !== BapStatus::NeedsClarification || ! $lockedClarification->status->canReceiveResponse()) {
                throw ValidationException::withMessages([
                    'status' => 'Klarifikasi ini sudah tidak berada pada state yang dapat dibuka.',
                ]);
            }

            if ($lockedClarification->opened_at !== null) {
                return;
            }

            $openedAt = now();
            $lockedClarification->openedBy()->associate($actor);
            $lockedClarification->opened_at = $openedAt;
            $lockedClarification->save();

            $this->audit->handle($actor, $lockedBap, 'bap_clarification.opened', null, [
                'clarification_id' => $lockedClarification->id,
                'opened_at' => $openedAt->toISOString(),
            ]);
        }, attempts: 3);
    }
}
