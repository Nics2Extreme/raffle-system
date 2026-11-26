<?php

namespace Database\Factories;

use App\Enums\PrizeType;
use App\Models\Prize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prize>
 */
class PrizeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);

        return [
            'name' => fake()->unique()->words(3, true),
            'quantity' => $quantity,
            'remaining' => $quantity,
            'type' => fake()->randomElement(PrizeType::cases()),
        ];
    }

    public function major(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PrizeType::Major,
        ]);
    }

    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PrizeType::Minor,
        ]);
    }

    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining' => 0,
        ]);
    }
}
