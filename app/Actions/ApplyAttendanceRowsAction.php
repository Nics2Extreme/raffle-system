<?php

namespace App\Actions;

use App\Models\Participant;
use App\Support\AttendanceWorksheet;
use App\Support\EmployeeNumber;
use Illuminate\Support\Facades\DB;

class ApplyAttendanceRowsAction
{
    /**
     * @param  array<int, array<int, string|null>>  $rows
     * @param  array{full_name: int|null, employee_number: int, attendance: int}  $columns
     * @return array{present: int, absent: int, skipped: int}
     */
    public function handle(array $rows, array $columns): array
    {
        $present = 0;
        $absent = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $columns, &$present, &$absent, &$skipped): void {
            foreach ($rows as $row) {
                if (AttendanceWorksheet::rowIsBlank($row)) {
                    continue;
                }

                $employeeNumber = EmployeeNumber::tryNormalize((string) ($row[$columns['employee_number']] ?? ''));
                $attended = AttendanceWorksheet::parseAttendance((string) ($row[$columns['attendance']] ?? ''));
                $fullName = $columns['full_name'] !== null
                    ? trim((string) ($row[$columns['full_name']] ?? ''))
                    : '';

                if ($employeeNumber === null || $attended === null) {
                    $skipped++;

                    continue;
                }

                $participant = Participant::query()
                    ->where('employee_number', $employeeNumber)
                    ->first();

                if ($participant === null) {
                    $skipped++;

                    continue;
                }

                if ($fullName !== '') {
                    $participant->full_name = $fullName;
                }

                $participant->attended = $attended;
                $participant->attended_at = $attended ? now() : null;
                $participant->save();

                if ($attended) {
                    $present++;
                } else {
                    $absent++;
                }
            }
        });

        return [
            'present' => $present,
            'absent' => $absent,
            'skipped' => $skipped,
        ];
    }
}
