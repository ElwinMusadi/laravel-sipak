<?php

namespace App\Models;

use Database\Factories\LoketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Loket extends Model
{
    /** @use HasFactory<LoketFactory> */
    use HasFactory;

    /**
     * Get the users assigned to the loket.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the SKPD allocations for this loket.
     *
     * @return HasMany<SkpdAllocation, $this>
     */
    public function skpdAllocations(): HasMany
    {
        return $this->hasMany(SkpdAllocation::class);
    }

    /**
     * Get the daily BAP records for this loket.
     *
     * @return HasMany<Bap, $this>
     */
    public function baps(): HasMany
    {
        return $this->hasMany(Bap::class);
    }
}
