<?php

namespace App\Models;

use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use Carbon\CarbonInterface;
use Database\Factories\BapVerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $bap_id
 * @property int $verifier_id
 * @property BapVerificationStage $stage
 * @property int $attempt
 * @property BapVerificationStatus $status
 * @property BapVerificationResult|null $result
 * @property string|null $notes
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $completed_at
 */
#[Fillable(['bap_id', 'verifier_id', 'stage', 'attempt', 'status', 'result', 'notes', 'started_at', 'completed_at'])]
class BapVerification extends Model
{
    /** @use HasFactory<BapVerificationFactory> */
    use HasFactory;

    /**
     * Get the BAP being verified.
     *
     * @return BelongsTo<Bap, $this>
     */
    public function bap(): BelongsTo
    {
        return $this->belongsTo(Bap::class);
    }

    /**
     * Get the Petugas Penetapan who performs this verification.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    /**
     * Get the structured checklist recorded for this verification.
     *
     * @return HasMany<BapVerificationChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(BapVerificationChecklistItem::class);
    }

    /**
     * Get discrepancy findings recorded in this verification.
     *
     * @return HasMany<BapVerificationDiscrepancy, $this>
     */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(BapVerificationDiscrepancy::class);
    }

    /**
     * Get the minimum clarification request created for a discrepant BAP.
     *
     * @return HasOne<BapClarificationRequest, $this>
     */
    public function clarificationRequest(): HasOne
    {
        return $this->hasOne(BapClarificationRequest::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => BapVerificationStage::class,
            'status' => BapVerificationStatus::class,
            'result' => BapVerificationResult::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
