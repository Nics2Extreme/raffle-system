<?php

use App\Enums\ParticipantSource;
use App\Models\Participant;
use App\Models\User;

test('operators can add a latecomer who becomes immediately eligible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('participants.store'), [
            'full_name' => 'Late Arrival',
            'employee_number' => '42',
        ])
        ->assertRedirect(route('participants.index'));

    $participant = Participant::query()->first();

    expect($participant)
        ->full_name->toBe('Late Arrival')
        ->employee_number->toBe('0042')
        ->privacy_agreed->toBeTrue()
        ->source->toBe(ParticipantSource::Manual)
        ->and(Participant::query()->eligible()->count())->toBe(1);
});

test('latecomers cannot reuse an existing employee number', function () {
    $user = User::factory()->create();

    Participant::factory()->create([
        'employee_number' => '1001',
    ]);

    $this->actingAs($user)
        ->post(route('participants.store'), [
            'full_name' => 'Duplicate',
            'employee_number' => '1001',
        ])
        ->assertSessionHasErrors('employee_number');

    expect(Participant::query()->count())->toBe(1);
});
