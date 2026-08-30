<?php

namespace App\Models;

use App\BapCancellationReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bap_id
 * @property int $numerator
 * @property BapCancellationReason $reason
 * @property string|null $description
 * @property int $created_by
 */
#[Fillable(['bap_id', 'numerator', 'reason', 'description', 'created_by'])]
class BapCancellation extends Model
{
    /**
     * Get the BAP that contains this cancellation.
     *
     * @return BelongsTo<Bap, $this>
     */
    public function bap(): BelongsTo
    {
        return $this->belongsTo(Bap::class);
    }

    /**
     * Get the user who recorded this cancellation.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => BapCancellationReason::class,
        ];
    }
}
