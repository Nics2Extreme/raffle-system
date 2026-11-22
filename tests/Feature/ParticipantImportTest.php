<?php

use App\Enums\ParticipantSource;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function makeParticipantsCsv(string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'raffle');
    file_put_contents($path, $contents);

    return new UploadedFile($path, 'participants.csv', 'text/csv', null, true);
}

test('operators can import participants from a google form csv', function () {
    $user = User::factory()->create();

    $csv = makeParticipantsCsv(<<<'CSV'
Full Name,ID Number,Privacy Notice
Jane Doe,7598,Agree
John Smith,756,Disagree
Tiny Tim,3,Agree
CSV);

    $this->actingAs($user)
        ->post(route('participants.import'), ['file' => $csv])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->count())->toBe(3)
        ->and(Participant::query()->where('employee_number', '7598')->first())
        ->full_name->toBe('Jane Doe')
        ->privacy_agreed->toBeTrue()
        ->source->toBe(ParticipantSource::Import)
        ->and(Participant::query()->where('employee_number', '0756')->first())
        ->privacy_agreed->toBeFalse()
        ->and(Participant::query()->where('employee_number', '0003')->first())
        ->privacy_agreed->toBeTrue();
});

test('reimporting updates existing participants by employee number', function () {
    $user = User::factory()->create();

    Participant::factory()->create([
        'full_name' => 'Old Name',
        'employee_number' => '3145',
        'privacy_agreed' => false,
    ]);

    $csv = makeParticipantsCsv(<<<'CSV'
Full Name,ID Number,Privacy Notice
Updated Name,3145,Agree
CSV);

    $this->actingAs($user)
        ->post(route('participants.import'), ['file' => $csv])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->count())->toBe(1)
        ->and(Participant::query()->first())
        ->full_name->toBe('Updated Name')
        ->privacy_agreed->toBeTrue();
});

test('import skips invalid rows', function () {
    $user = User::factory()->create();

    $csv = makeParticipantsCsv(<<<'CSV'
Full Name,ID Number,Privacy Notice
Valid Person,89,Agree
,1234,Agree
Broken Person,12345,Agree
Mystery Person,55,Maybe
CSV);

    $this->actingAs($user)
        ->post(route('participants.import'), ['file' => $csv])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->count())->toBe(1)
        ->and(Participant::query()->first()->employee_number)->toBe('0089');
});
