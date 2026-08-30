<?php

namespace App\Actions\SkpdInventory;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordDomainAudit
{
    /**
     * Record a domain event through the Phase 03 audit foundation.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function handle(User $actor, Model $model, string $event, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
