<?php

namespace App\Models;

use App\SkpdAllocationStatus;
use Database\Factories\SkpdAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $skpd_box_id
 * @property int $loket_id
 * @property int $numerator_start
 * @property int $numerator_end
 * @property int $quantity
 * @property SkpdAllocationStatus $status
 * @property int $created_by
 * @property int|null $accepted_by
 * @property Carbon|null $accepted_at
 */
#[Fillable(['skpd_box_id', 'loket_id', 'numerator_start', 'numerator_end', 'quantity', 'status', 'created_by', 'accepted_by', 'accepted_at'])]
class SkpdAllocation extends Model
{
    /** @use HasFactory<SkpdAllocationFactory> */
    use HasFactory;

    /**
     * Get the source box for this allocation.
     *
     * @return BelongsTo<SkpdBox, $this>
     */
    public function skpdBox(): BelongsTo
    {
        return $this->belongsTo(SkpdBox::class);
    }

    /**
     * Get the administrative holder of this allocation.
     *
     * @return BelongsTo<Loket, $this>
     */
    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class);
    }

    /**
     * Get the user who created the allocation.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who digitally accepted the allocation.
     *
     * @return BelongsTo<User, $this>
     */
    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Get BAP segments consuming this allocation.
     *
     * @return HasMany<BapUsageSegment, $this>
     */
    public function usageSegments(): HasMany
    {
        return $this->hasMany(BapUsageSegment::class);
    }

    /**
     * Get the audit entries for this allocation.
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function usedQuantity(): int
    {
        return (int) $this->usageSegments()->sum('quantity');
    }

    public function remainingQuantity(): int
    {
        return $this->quantity - $this->usedQuantity();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SkpdAllocationStatus::class,
            'accepted_at' => 'datetime',
        ];
    }
}
