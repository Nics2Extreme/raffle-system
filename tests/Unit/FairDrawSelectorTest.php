<?php

use App\Exceptions\NoEligibleParticipantsException;
use App\Models\Participant;
use App\Support\FairDrawSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('selects by sorted employee number index using the provided rng', function () {
    $first = Participant::factory()->create(['employee_number' => '0100']);
    $second = Participant::factory()->create(['employee_number' => '0200']);
    $third = Participant::factory()->create(['employee_number' => '0300']);

    $selector = new FairDrawSelector(fn (int $min, int $max): int => 1);
    $result = $selector->select(collect([$third, $first, $second]));

    expect($result['participant']->is($second))->toBeTrue()
        ->and($result['selected_index'])->toBe(1)
        ->and($result['pool_count'])->toBe(3)
        ->and($result['algorithm'])->toBe(FairDrawSelector::ALGORITHM)
        ->and($result['pool_hash'])->toBe(hash('sha256', "0100\n0200\n0300"));
});

test('describePool publishes a deterministic hash for the current eligible list', function () {
    Participant::factory()->create(['employee_number' => '0003']);
    Participant::factory()->create(['employee_number' => '0001']);

    $selector = new FairDrawSelector;
    $description = $selector->describePool(
        Participant::query()->orderByDesc('employee_number')->get(),
    );

    expect($description)
        ->pool_count->toBe(2)
        ->employee_numbers->toBe(['0001', '0003'])
        ->pool_hash->toBe(hash('sha256', "0001\n0003"));
});

test('select throws when the pool is empty', function () {
    $selector = new FairDrawSelector;

    $selector->select(collect());
})->throws(NoEligibleParticipantsException::class);
