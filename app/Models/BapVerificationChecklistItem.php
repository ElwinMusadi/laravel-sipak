<?php

namespace App\Models;

use App\BapVerificationChecklistType;
use Database\Factories\BapVerificationChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bap_verification_id
 * @property BapVerificationChecklistType $type
 * @property bool $is_attested
 * @property int|null $expected_quantity
 * @property int|null $actual_quantity
 * @property int|null $quantity_difference
 * @property int|null $expected_numerator_start
 * @property int|null $expected_numerator_end
 * @property int|null $actual_numerator_start
 * @property int|null $actual_numerator_end
 */
#[Fillable(['bap_verification_id', 'type', 'is_attested', 'expected_quantity', 'actual_quantity', 'quantity_difference', 'expected_numerator_start', 'expected_numerator_end', 'actual_numerator_start', 'actual_numerator_end'])]
class BapVerificationChecklistItem extends Model
{
    /** @use HasFactory<BapVerificationChecklistItemFactory> */
    use HasFactory;

    /**
     * Get the verification that owns this checklist item.
     *
     * @return BelongsTo<BapVerification, $this>
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(BapVerification::class, 'bap_verification_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BapVerificationChecklistType::class,
            'is_attested' => 'boolean',
        ];
    }
}
