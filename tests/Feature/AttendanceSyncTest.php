<?php

use App\Contracts\ReadsGoogleSheets;
use App\Models\AttendanceSetting;
use App\Models\Participant;
use App\Models\User;

test('google sheet sync updates attendance and stores settings', function () {
    $user = User::factory()->create();

    Participant::factory()->create([
        'full_name' => 'Old Name',
        'employee_number' => '3219',
        'attended' => false,
    ]);
    Participant::factory()->attended()->create([
        'employee_number' => '0042',
    ]);

    $this->app->instance(ReadsGoogleSheets::class, new class implements ReadsGoogleSheets
    {
        public function values(string $spreadsheetId, string $range): array
        {
            return [
                ['Full Name', 'Emp No', 'Status'],
                ['Updated Name', '3219', 'Present'],
                ['Someone', '42', 'Absent'],
                ['Unknown', '8888', 'Present'],
            ];
        }
    });

    $this->actingAs($user)
        ->post(route('participants.attendance.sync'), [
            'spreadsheet_id' => 'sheet-123',
            'sheet_range' => 'Attendance!A:C',
        ])
        ->assertRedirect(route('participants.index'));

    expect(Participant::query()->where('employee_number', '3219')->first())
        ->full_name->toBe('Updated Name')
        ->attended->toBeTrue()
        ->and(Participant::query()->where('employee_number', '0042')->first())
        ->attended->toBeFalse()
        ->and(Participant::query()->count())->toBe(2)
        ->and(AttendanceSetting::current())
        ->spreadsheet_id->toBe('sheet-123')
        ->sheet_range->toBe('Attendance!A:C');
});

test('google sheet sync requires a spreadsheet id', function () {
    $user = User::factory()->create();

    $this->app->instance(ReadsGoogleSheets::class, new class implements ReadsGoogleSheets
    {
        public function values(string $spreadsheetId, string $range): array
        {
            return [];
        }
    });

    $this->actingAs($user)
        ->post(route('participants.attendance.sync'), [
            'spreadsheet_id' => '',
            'sheet_range' => 'Attendance!A:C',
        ])
        ->assertSessionHasErrors('spreadsheet_id');
});
