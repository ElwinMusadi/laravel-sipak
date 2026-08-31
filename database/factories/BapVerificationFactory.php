<?php

namespace Database\Factories;

use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Models\Bap;
use App\Models\BapVerification;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapVerification>
 */
class BapVerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bap_id' => Bap::factory(),
            'verifier_id' => User::factory()->state([
                'role' => UserRole::PetugasPenetapan,
            ]),
            'stage' => BapVerificationStage::Phase1,
            'attempt' => 1,
            'status' => BapVerificationStatus::InProgress,
            'result' => null,
            'notes' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(BapVerificationResult $result = BapVerificationResult::Passed): static
    {
        return $this->state([
            'status' => BapVerificationStatus::Completed,
            'result' => $result,
            'completed_at' => now(),
        ]);
    }
}
