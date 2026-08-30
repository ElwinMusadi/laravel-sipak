<?php

namespace App\Actions\UserManagement;

use App\Models\AuditLog;
use App\Models\User;

class RecordUserManagementAudit
{
    /**
     * Record a new user without recording their password.
     */
    public function created(User $actor, User $user): void
    {
        $this->record($actor, $user, 'user.created', null, $this->attributes($user));
    }

    /**
     * Record each administrative change as a discrete audit event.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $changes
     */
    public function updated(User $actor, User $user, array $old, array $changes): void
    {
        $events = [
            'username' => 'user.username_changed',
            'name' => 'user.name_changed',
            'role' => 'user.role_changed',
            'loket_id' => 'user.loket_changed',
            'is_active' => $user->is_active ? 'user.activated' : 'user.deactivated',
        ];

        foreach ($events as $attribute => $event) {
            if (array_key_exists($attribute, $changes)) {
                $this->record(
                    $actor,
                    $user,
                    $event,
                    [$attribute => $old[$attribute] ?? null],
                    [$attribute => $changes[$attribute]],
                );
            }
        }
    }

    /**
     * Record a password reset without persisting any password value or hash.
     */
    public function passwordReset(User $actor, User $user): void
    {
        $this->record($actor, $user, 'user.password_reset', null, null);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(User $user): array
    {
        return [
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role->value,
            'loket_id' => $user->loket_id,
            'is_active' => $user->is_active,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function record(User $actor, User $user, string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
