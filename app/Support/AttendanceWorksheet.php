<?php

namespace App\Support;

use InvalidArgumentException;

class AttendanceWorksheet
{
    /**
     * @param  array<int, string|null>  $header
     * @return array{full_name: int|null, employee_number: int, attendance: int}
     */
    public static function mapColumns(array $header): array
    {
        $normalized = array_map(
            fn (?string $column): string => strtolower(trim((string) $column)),
            $header,
        );

        $fullName = self::findColumnIndex($normalized, [
            'full name',
            'name',
            'employee name',
        ]);
        $employeeNumber = self::findColumnIndex($normalized, [
            'id number',
            'employee no.',
            'employee no',
            'employee number',
            'emp no',
            'emp no.',
        ]);
        $attendance = self::findColumnIndex($normalized, [
            'present/absent',
            'present / absent',
            'attendance',
            'status',
            'present',
        ]);

        if ($employeeNumber === null || $attendance === null) {
            throw new InvalidArgumentException(
                'Attendance worksheet must include Employee Number (or ID Number) and Present/Absent (or Attendance/Status) columns.',
            );
        }

        return [
            'full_name' => $fullName,
            'employee_number' => $employeeNumber,
            'attendance' => $attendance,
        ];
    }

    public static function parseAttendance(string $value): ?bool
    {
        $normalized = strtolower(trim($value));

        return match (true) {
            in_array($normalized, ['present', 'yes', 'y', 'true', '1', 'attended', 'here'], true) => true,
            in_array($normalized, ['absent', 'no', 'n', 'false', '0', 'not present'], true) => false,
            default => null,
        };
    }

    /**
     * @param  array<int, string|null>  $row
     */
    public static function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $normalized
     * @param  array<int, string>  $aliases
     */
    private static function findColumnIndex(array $normalized, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $normalized, true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }
}
