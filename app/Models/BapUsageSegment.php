<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bap_id
 * @property int $skpd_allocation_id
 * @property int $numerator_start
 * @property int $numerator_end
 * @property int $quantity
 */
#[Fillable(['bap_id', 'skpd_allocation_id', 'numerator_start', 'numerator_end', 'quantity'])]
class BapUsageSegment extends Model
{
    /**
     * Get the BAP that owns this segment.
     *
     * @return BelongsTo<Bap, $this>
     */
    public function bap(): BelongsTo
    {
        return $this->belongsTo(Bap::class);
    }

    /**
     * Get the allocation consumed by this segment.
     *
     * @return BelongsTo<SkpdAllocation, $this>
     */
    public function skpdAllocation(): BelongsTo
    {
        return $this->belongsTo(SkpdAllocation::class);
    }
}
