<?php

namespace App\Models;

use App\BapVerificationChecklistType;
use Database\Factories\BapVerificationDiscrepancyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bap_verification_id
 * @property int $bap_verification_checklist_item_id
 * @property BapVerificationChecklistType $type
 * @property string $expected_value
 * @property string $actual_value
 * @property int|null $difference
 * @property string $notes
 */
#[Fillable(['bap_verification_id', 'bap_verification_checklist_item_id', 'type', 'expected_value', 'actual_value', 'difference', 'notes'])]
class BapVerificationDiscrepancy extends Model
{
    /** @use HasFactory<BapVerificationDiscrepancyFactory> */
    use HasFactory;

    /**
     * Get the verification that contains this finding.
     *
     * @return BelongsTo<BapVerification, $this>
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(BapVerification::class, 'bap_verification_id');
    }

    /**
     * Get the exact checklist item that produced this finding.
     *
     * @return BelongsTo<BapVerificationChecklistItem, $this>
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(BapVerificationChecklistItem::class, 'bap_verification_checklist_item_id');
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
        ];
    }
}
