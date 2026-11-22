<?php

namespace App\Actions;

use App\Contracts\ReadsGoogleSheets;
use App\Models\AttendanceSetting;
use App\Support\AttendanceWorksheet;
use InvalidArgumentException;

class SyncAttendanceFromGoogleSheetAction
{
    public function __construct(
        private ReadsGoogleSheets $sheets,
        private ApplyAttendanceRowsAction $applyAttendanceRows,
    ) {}

    /**
     * @return array{present: int, absent: int, skipped: int}
     */
    public function handle(?string $spreadsheetId = null, ?string $range = null): array
    {
        $settings = AttendanceSetting::current();

        $spreadsheetId = $spreadsheetId !== null && trim($spreadsheetId) !== ''
            ? trim($spreadsheetId)
            : $settings->spreadsheet_id;

        $range = $range !== null && trim($range) !== ''
            ? trim($range)
            : $settings->sheet_range;

        if ($spreadsheetId === null || $spreadsheetId === '') {
            throw new InvalidArgumentException('A Google Spreadsheet ID is required to sync attendance.');
        }

        if ($range === '') {
            $range = 'Attendance!A:C';
        }

        $settings->fill([
            'spreadsheet_id' => $spreadsheetId,
            'sheet_range' => $range,
        ])->save();

        $values = $this->sheets->values($spreadsheetId, $range);

        if ($values === []) {
            throw new InvalidArgumentException('The Google Sheet range returned no rows.');
        }

        $header = array_shift($values);
        $columns = AttendanceWorksheet::mapColumns($header);

        return $this->applyAttendanceRows->handle(array_values($values), $columns);
    }
}
