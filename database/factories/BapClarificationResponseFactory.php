<?php

namespace Database\Factories;

use App\Models\BapClarificationRequest;
use App\Models\BapClarificationResponse;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapClarificationResponse>
 */
class BapClarificationResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bap_clarification_request_id' => BapClarificationRequest::factory(),
            'round' => 1,
            'responded_by' => User::factory()->state(['role' => UserRole::PetugasLoket]),
            'response' => 'Pengecekan ulang Loket telah dilakukan.',
            'responded_at' => now(),
        ];
    }
}
