<?php

namespace App\Models;

use App\BapClarificationStatus;
use Carbon\CarbonInterface;
use Database\Factories\BapClarificationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $bap_id
 * @property int $bap_verification_id
 * @property int $requested_by
 * @property int|null $opened_by
 * @property CarbonInterface|null $opened_at
 * @property BapClarificationStatus $status
 * @property string|null $notes
 */
#[Fillable(['bap_id', 'bap_verification_id', 'requested_by', 'opened_by', 'opened_at', 'status', 'notes'])]
class BapClarificationRequest extends Model
{
    /** @use HasFactory<BapClarificationRequestFactory> */
    use HasFactory;

    /**
     * Get the BAP that needs clarification.
     *
     * @return BelongsTo<Bap, $this>
     */
    public function bap(): BelongsTo
    {
        return $this->belongsTo(Bap::class);
    }

    /**
     * Get the verification that requested clarification.
     *
     * @return BelongsTo<BapVerification, $this>
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(BapVerification::class, 'bap_verification_id');
    }

    /**
     * Get the verifier that made this request.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the first Petugas Loket who opened this clarification.
     *
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Get all responses supplied by the BAP's Loket.
     *
     * @return HasMany<BapClarificationResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(BapClarificationResponse::class);
    }

    /**
     * Get all reviewer decisions made over clarification responses.
     *
     * @return HasMany<BapClarificationResolution, $this>
     */
    public function resolutions(): HasMany
    {
        return $this->hasMany(BapClarificationResolution::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BapClarificationStatus::class,
            'opened_at' => 'datetime',
        ];
    }
}
