<?php

namespace App\Models;

use App\BapStatus;
use Carbon\CarbonInterface;
use Database\Factories\BapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * @property int $id
 * @property int $loket_id
 * @property CarbonInterface $service_date
 * @property int $numerator_start
 * @property int $numerator_end
 * @property int $total_usage
 * @property int $online_usage_count
 * @property BapStatus $status
 * @property int $created_by
 * @property CarbonInterface|null $submitted_at
 * @property int|null $received_by
 * @property CarbonInterface|null $received_at
 * @property string|null $receipt_notes
 */
#[Fillable(['loket_id', 'service_date', 'numerator_start', 'numerator_end', 'total_usage', 'online_usage_count', 'status', 'created_by', 'submitted_at', 'received_by', 'received_at', 'receipt_notes'])]
class Bap extends Model
{
    /** @use HasFactory<BapFactory> */
    use HasFactory;

    /**
     * Get the loket that produced this BAP.
     *
     * @return BelongsTo<Loket, $this>
     */
    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class);
    }

    /**
     * Get the user who created this BAP.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the Bendahara Barang who received this BAP administratively.
     *
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get allocation consumption segments for this BAP.
     *
     * @return HasMany<BapUsageSegment, $this>
     */
    public function usageSegments(): HasMany
    {
        return $this->hasMany(BapUsageSegment::class);
    }

    /**
     * Get cancelled or damaged numerators in this BAP range.
     *
     * @return HasMany<BapCancellation, $this>
     */
    public function cancellations(): HasMany
    {
        return $this->hasMany(BapCancellation::class);
    }

    /**
     * Get verification attempts recorded for this BAP.
     *
     * @return HasMany<BapVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(BapVerification::class);
    }

    /**
     * Get minimum clarification requests associated with this BAP.
     *
     * @return HasMany<BapClarificationRequest, $this>
     */
    public function clarificationRequests(): HasMany
    {
        return $this->hasMany(BapClarificationRequest::class);
    }

    /**
     * Get the audit entries for this BAP.
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function normalUsageQuantity(): int
    {
        return $this->total_usage - $this->cancellations()->count();
    }

    public function transitionTo(BapStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new LogicException("BAP tidak dapat berpindah dari {$this->status->value} ke {$status->value}.");
        }

        $this->status = $status;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'status' => BapStatus::class,
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }
}
