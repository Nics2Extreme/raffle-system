<?php

namespace App\Actions;

use App\Support\AttendanceWorksheet;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ImportAttendanceAction
{
    public function __construct(private ApplyAttendanceRowsAction $applyAttendanceRows) {}

    /**
     * @return array{present: int, absent: int, skipped: int}
     */
    public function handle(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read the uploaded CSV file.');
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new InvalidArgumentException('The CSV file is empty.');
        }

        $columns = AttendanceWorksheet::mapColumns($header);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $this->applyAttendanceRows->handle($rows, $columns);
    }
}
