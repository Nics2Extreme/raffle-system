<?php

use App\Models\Draw;
use App\Models\Participant;
use App\Models\User;

test('winners can be sorted by employee number ascending', function () {
    $operator = User::factory()->create();

    $first = Participant::factory()->attended()->create([
        'employee_number' => '0100',
        'full_name' => 'Alpha',
    ]);
    $second = Participant::factory()->attended()->create([
        'employee_number' => '0200',
        'full_name' => 'Bravo',
    ]);

    Draw::factory()->create([
        'participant_id' => $second->id,
        'drawn_by' => $operator->id,
        'drawn_at' => now()->subMinute(),
    ]);
    Draw::factory()->create([
        'participant_id' => $first->id,
        'drawn_by' => $operator->id,
        'drawn_at' => now(),
    ]);

    $this->actingAs($operator)
        ->get(route('winners.index', [
            'sort' => 'employee_number',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('winners/index')
            ->where('filters.sort', 'employee_number')
            ->where('filters.direction', 'asc')
            ->where('winners.data.0.employee_number', '0100')
            ->where('winners.data.1.employee_number', '0200'));
});

test('invalid sort columns fall back to drawn_at descending', function () {
    $operator = User::factory()->create();

    $this->actingAs($operator)
        ->get(route('winners.index', [
            'sort' => 'drop table',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'drawn_at')
            ->where('filters.direction', 'desc'));
});
