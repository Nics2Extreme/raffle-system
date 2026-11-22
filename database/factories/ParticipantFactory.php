<?php

namespace Database\Factories;

use App\Enums\ParticipantSource;
use App\Models\Participant;
use App\Support\EmployeeNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'employee_number' => EmployeeNumber::normalize((string) fake()->unique()->numberBetween(1, 9999)),
            'privacy_agreed' => true,
            'attended' => false,
            'attended_at' => null,
            'source' => ParticipantSource::Import,
        ];
    }

    public function attended(): static
    {
        return $this->state(fn (array $attributes) => [
            'attended' => true,
            'attended_at' => now(),
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'attended' => false,
            'attended_at' => null,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy_agreed' => false,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => ParticipantSource::Manual,
        ]);
    }
}
