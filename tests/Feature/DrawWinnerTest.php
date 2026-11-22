<?php

use App\Models\Draw;
use App\Models\Participant;
use App\Models\User;
use App\Support\FairDrawSelector;

test('drawing selects an eligible participant and persists the win', function () {
    $user = User::factory()->create();
    $eligible = Participant::factory()->attended()->create([
        'employee_number' => '3219',
        'full_name' => 'Eligible Winner',
    ]);
    Participant::factory()->declined()->create();
    Participant::factory()->create(['employee_number' => '9999']);

    $this->actingAs($user)
        ->post(route('draw.store'), [
            'prize_label' => 'Grand Prize',
        ])
        ->assertRedirect(route('draw.index'))
        ->assertSessionHas('drawn_winner', [
            'full_name' => 'Eligible Winner',
            'employee_number' => '3219',
            'prize_label' => 'Grand Prize',
        ]);

    $draw = Draw::query()->first();

    expect(Draw::query()->count())->toBe(1)
        ->and($draw)
        ->participant_id->toBe($eligible->id)
        ->drawn_by->toBe($user->id)
        ->prize_label->toBe('Grand Prize')
        ->algorithm->toBe(FairDrawSelector::ALGORITHM)
        ->pool_count->toBe(1)
        ->selected_index->toBe(0)
        ->pool_hash->toBe(hash('sha256', '3219'))
        ->and(Participant::query()->eligible()->count())->toBe(0);
});

test('drawing uses a sorted pool index from the fair selector', function () {
    $user = User::factory()->create();

    Participant::factory()->attended()->create(['employee_number' => '0100', 'full_name' => 'A']);
    $target = Participant::factory()->attended()->create(['employee_number' => '0200', 'full_name' => 'B']);
    Participant::factory()->attended()->create(['employee_number' => '0300', 'full_name' => 'C']);

    $this->app->instance(
        FairDrawSelector::class,
        new FairDrawSelector(fn (int $min, int $max): int => 1),
    );

    $this->actingAs($user)
        ->post(route('draw.store'))
        ->assertRedirect(route('draw.index'))
        ->assertSessionHas('drawn_winner.employee_number', '0200');

    expect(Draw::query()->first())
        ->participant_id->toBe($target->id)
        ->selected_index->toBe(1)
        ->pool_count->toBe(3)
        ->pool_hash->toBe(hash('sha256', "0100\n0200\n0300"));
});

test('draw page publishes the current eligible pool hash', function () {
    $user = User::factory()->create();

    Participant::factory()->attended()->create(['employee_number' => '0002']);
    Participant::factory()->attended()->create(['employee_number' => '0001']);

    $this->actingAs($user)
        ->get(route('draw.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('draw/index')
            ->where('fairness.pool_count', 2)
            ->where('fairness.pool_hash', hash('sha256', "0001\n0002"))
            ->where('fairness.algorithm', FairDrawSelector::ALGORITHM));
});

test('a participant can only win once', function () {
    $user = User::factory()->create();
    $first = Participant::factory()->attended()->create(['employee_number' => '1111']);
    $second = Participant::factory()->attended()->create(['employee_number' => '2222']);

    $this->actingAs($user)->post(route('draw.store'))->assertRedirect(route('draw.index'));
    $this->actingAs($user)->post(route('draw.store'))->assertRedirect(route('draw.index'));

    $winnerIds = Draw::query()->pluck('participant_id')->sort()->values()->all();

    expect($winnerIds)->toBe(
        collect([$first->id, $second->id])->sort()->values()->all(),
    )
        ->and(Participant::query()->eligible()->count())->toBe(0);
});

test('declined privacy participants are excluded from draws', function () {
    $user = User::factory()->create();
    Participant::factory()->declined()->attended()->create();

    $this->actingAs($user)
        ->post(route('draw.store'))
        ->assertSessionHasErrors('draw');

    expect(Draw::query()->count())->toBe(0);
});

test('registered but not present participants are excluded from draws', function () {
    $user = User::factory()->create();
    Participant::factory()->create([
        'employee_number' => '4444',
        'privacy_agreed' => true,
        'attended' => false,
    ]);

    $this->actingAs($user)
        ->post(route('draw.store'))
        ->assertSessionHasErrors('draw');

    expect(Draw::query()->count())->toBe(0);
});

test('latecomers added mid-event can win subsequent draws', function () {
    $user = User::factory()->create();
    $early = Participant::factory()->attended()->create(['employee_number' => '5001']);

    $this->actingAs($user)->post(route('draw.store'))->assertRedirect(route('draw.index'));

    expect(Draw::query()->where('participant_id', $early->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->post(route('participants.store'), [
            'full_name' => 'Late Comer',
            'employee_number' => '77',
        ])
        ->assertRedirect(route('participants.index'));

    $this->actingAs($user)
        ->post(route('draw.store'))
        ->assertRedirect(route('draw.index'))
        ->assertSessionHas('drawn_winner.employee_number', '0077');
});

test('draw fails when the eligible pool is empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('draw.store'))
        ->assertSessionHasErrors('draw');
});
