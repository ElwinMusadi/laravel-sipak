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
}
