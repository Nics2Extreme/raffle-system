<?php

use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function makeAttendanceCsv(string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'attendance');
    file_put_contents($path, $contents);

    return new UploadedFile($path, 'attendance.csv', 'text/csv', null, true);
}

test('attendance csv marks present and absent for registered employees', function () {
    $user = User::factory()->create();

    Participant::factory()->create([
        'full_name' => 'Jane Doe',
        'employee_number' => '7598',
        'privacy_agreed' => true,
        'attended' => false,
    ]);
    Participant::factory()->attended()->create([
        'full_name' => 'John Smith',
        'employee_number' => '0756',
        'privacy_agreed' => true,
    ]);

    $csv = makeAttendanceCsv(<<<'CSV'
Full Name,ID Number,Present/Absent
Jane Doe,7598,Present
John Smith,756,Absent
CSV);

    $this->actingAs($user)
        ->post(route('participants.attendance.import'), ['file' => $csv])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->where('employee_number', '7598')->first())
        ->attended->toBeTrue()
        ->and(Participant::query()->where('employee_number', '0756')->first())
        ->attended->toBeFalse()
        ->attended_at->toBeNull()
        ->and(Participant::query()->eligible()->count())->toBe(1);
});

test('attendance csv skips unknown employee numbers', function () {
    $user = User::factory()->create();

    Participant::factory()->create([
        'employee_number' => '1000',
        'attended' => false,
    ]);

    $csv = makeAttendanceCsv(<<<'CSV'
Full Name,ID Number,Attendance
Known Person,1000,Present
Ghost Person,9999,Present
CSV);

    $this->actingAs($user)
        ->post(route('participants.attendance.import'), ['file' => $csv])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->count())->toBe(1)
        ->and(Participant::query()->first()->attended)->toBeTrue();
});
