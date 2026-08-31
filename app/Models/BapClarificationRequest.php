<?php

namespace App\Models;

use App\BapClarificationStatus;
use Database\Factories\BapClarificationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bap_id
 * @property int $bap_verification_id
 * @property int $requested_by
 * @property BapClarificationStatus $status
 * @property string|null $notes
 */
#[Fillable(['bap_id', 'bap_verification_id', 'requested_by', 'status', 'notes'])]
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BapClarificationStatus::class,
        ];
    }
}
