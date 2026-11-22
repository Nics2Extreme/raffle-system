<?php

namespace Database\Factories;

use App\Models\Draw;
use App\Models\Participant;
use App\Models\User;
use App\Support\FairDrawSelector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Draw>
 */
class DrawFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'drawn_by' => User::factory(),
            'prize_label' => fake()->optional()->words(2, true),
            'algorithm' => FairDrawSelector::ALGORITHM,
            'pool_count' => fake()->numberBetween(1, 100),
            'pool_hash' => hash('sha256', fake()->uuid()),
            'selected_index' => fake()->numberBetween(0, 50),
            'drawn_at' => now(),
        ];
    }
}
