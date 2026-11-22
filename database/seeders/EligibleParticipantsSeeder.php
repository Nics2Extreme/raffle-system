<?php

namespace Database\Seeders;

use App\Enums\ParticipantSource;
use App\Models\Draw;
use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EligibleParticipantsSeeder extends Seeder
{
    public const COUNT = 250;

    /**
     * Seed a large eligible participant pool and matching worksheet CSVs.
     */
    public function run(): void
    {
        Draw::query()->delete();
        Participant::query()->delete();

        $rows = [];

        for ($number = 1; $number <= self::COUNT; $number++) {
            $employeeNumber = str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $fullName = fake()->name();

            $rows[] = [
                'full_name' => $fullName,
                'employee_number' => $employeeNumber,
                'privacy_agreed' => true,
                'attended' => true,
                'attended_at' => now(),
                'source' => ParticipantSource::Import->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Participant::query()->insert($chunk);
        }

        $this->writeWorksheets($rows);

        $this->command?->info('Seeded '.self::COUNT.' eligible participants and refreshed samples/*.csv worksheets.');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeWorksheets(array $rows): void
    {
        $samplesPath = base_path('samples');
        File::ensureDirectoryExists($samplesPath);

        $registration = fopen($samplesPath.'/registration-200plus.csv', 'w');
        $attendance = fopen($samplesPath.'/attendance-200plus.csv', 'w');

        if ($registration === false || $attendance === false) {
            throw new \RuntimeException('Unable to write sample worksheet files.');
        }

        fputcsv($registration, ['Full Name', 'ID Number', 'Privacy Notice']);
        fputcsv($attendance, ['Full Name', 'ID Number', 'Present/Absent']);

        foreach ($rows as $row) {
            fputcsv($registration, [
                $row['full_name'],
                ltrim($row['employee_number'], '0') === '' ? '0' : ltrim($row['employee_number'], '0'),
                'Agree',
            ]);
            fputcsv($attendance, [
                $row['full_name'],
                ltrim($row['employee_number'], '0') === '' ? '0' : ltrim($row['employee_number'], '0'),
                'Present',
            ]);
        }

        // Also write zero-padded ID versions for convenience.
        $registrationPadded = fopen($samplesPath.'/registration-200plus-padded.csv', 'w');
        $attendancePadded = fopen($samplesPath.'/attendance-200plus-padded.csv', 'w');

        if ($registrationPadded === false || $attendancePadded === false) {
            throw new \RuntimeException('Unable to write padded sample worksheet files.');
        }

        fputcsv($registrationPadded, ['Full Name', 'ID Number', 'Privacy Notice']);
        fputcsv($attendancePadded, ['Full Name', 'ID Number', 'Present/Absent']);

        foreach ($rows as $row) {
            fputcsv($registrationPadded, [$row['full_name'], $row['employee_number'], 'Agree']);
            fputcsv($attendancePadded, [$row['full_name'], $row['employee_number'], 'Present']);
        }

        fclose($registration);
        fclose($attendance);
        fclose($registrationPadded);
        fclose($attendancePadded);
    }
}
