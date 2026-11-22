<?php

use App\Models\Draw;
use App\Models\Participant;
use App\Models\User;

test('winners can be filtered by search prize and operator', function () {
    $operatorA = User::factory()->create(['name' => 'Operator A']);
    $operatorB = User::factory()->create(['name' => 'Operator B']);

    $alice = Participant::factory()->attended()->create([
        'full_name' => 'Alice Winner',
        'employee_number' => '1111',
    ]);
    $bob = Participant::factory()->attended()->create([
        'full_name' => 'Bob Runner',
        'employee_number' => '2222',
    ]);

    Draw::factory()->create([
        'participant_id' => $alice->id,
        'drawn_by' => $operatorA->id,
        'prize_label' => 'Grand Prize',
    ]);
    Draw::factory()->create([
        'participant_id' => $bob->id,
        'drawn_by' => $operatorB->id,
        'prize_label' => 'Consolation',
    ]);

    $this->actingAs($operatorA)
        ->get(route('winners.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('winners/index')
            ->has('winners.data', 1)
            ->where('winners.data.0.full_name', 'Alice Winner')
            ->where('filters.search', 'Alice'));

    $this->actingAs($operatorA)
        ->get(route('winners.index', ['prize' => 'Consolation']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('winners.data', 1)
            ->where('winners.data.0.employee_number', '2222'));

    $this->actingAs($operatorA)
        ->get(route('winners.index', ['operator_id' => $operatorA->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('winners.data', 1)
            ->where('winners.data.0.drawn_by', 'Operator A'));

    $this->actingAs($operatorA)
        ->get(route('winners.index', ['search' => '22']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('winners.data', 1)
            ->where('winners.data.0.employee_number', '2222'));
});
