<?php

namespace App\Models;

use App\SkpdAllocationStatus;
use App\SkpdBoxStatus;
use Database\Factories\SkpdBoxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $box_number
 * @property int $numerator_start
 * @property int $numerator_end
 * @property int $total_sets
 * @property string $central_storage_location
 * @property int $created_by
 * @property Carbon $received_at
 */
#[Fillable(['box_number', 'numerator_start', 'numerator_end', 'total_sets', 'central_storage_location', 'created_by', 'received_at'])]
class SkpdBox extends Model
{
    /** @use HasFactory<SkpdBoxFactory> */
    use HasFactory;

    /**
     * Get the user who registered the box.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the allocation ledger rows for this box.
     *
     * @return HasMany<SkpdAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(SkpdAllocation::class);
    }

    /**
     * Get the audit entries for this box.
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function pendingQuantity(): int
    {
        return (int) $this->allocations()
            ->where('status', SkpdAllocationStatus::Pending)
            ->sum('quantity');
    }

    public function administrativelyAllocatedQuantity(): int
    {
        return (int) $this->allocations()
            ->whereIn('status', [SkpdAllocationStatus::Accepted, SkpdAllocationStatus::Completed])
            ->sum('quantity');
    }

    public function centralPhysicalQuantity(): int
    {
        return $this->total_sets - $this->administrativelyAllocatedQuantity();
    }

    public function availableQuantity(): int
    {
        return $this->total_sets - $this->pendingQuantity() - $this->administrativelyAllocatedQuantity();
    }

    public function usedQuantity(): int
    {
        return (int) $this->allocations()
            ->withSum('usageSegments', 'quantity')
            ->get()
            ->sum('usage_segments_sum_quantity');
    }

    public function status(): SkpdBoxStatus
    {
        $allocatedQuantity = $this->pendingQuantity() + $this->administrativelyAllocatedQuantity();

        if ($allocatedQuantity === 0) {
            return SkpdBoxStatus::Available;
        }

        if ($allocatedQuantity < $this->total_sets) {
            return SkpdBoxStatus::PartiallyAllocated;
        }

        return $this->usedQuantity() === $this->total_sets
            ? SkpdBoxStatus::Depleted
            : SkpdBoxStatus::FullyAllocated;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }
}
