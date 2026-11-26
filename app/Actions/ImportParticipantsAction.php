<?php

namespace App\Actions;

use App\Enums\ParticipantSource;
use App\Models\Participant;
use App\Support\EmployeeNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ImportParticipantsAction
{
    /**
     * @return array{imported: int, updated: int, skipped: int}
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

        $columns = $this->mapColumns($header);
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($handle, $columns, &$imported, &$updated, &$skipped): void {
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->rowIsBlank($row)) {
                    continue;
                }

                $fullName = trim((string) ($row[$columns['full_name']] ?? ''));
                $employeeNumber = EmployeeNumber::tryNormalize((string) ($row[$columns['employee_number']] ?? ''));

                if ($fullName === '' || $employeeNumber === null) {
                    $skipped++;

                    continue;
                }

                $privacyAgreed = true;

                if ($columns['privacy'] !== null) {
                    $parsed = $this->parsePrivacyConsent((string) ($row[$columns['privacy']] ?? ''));
                    if ($parsed !== null) {
                        $privacyAgreed = $parsed;
                    }
                }

                $participant = Participant::query()->firstOrNew([
                    'employee_number' => $employeeNumber,
                ]);

                $wasExisting = $participant->exists;

                $participant->fill([
                    'full_name' => $fullName,
                    'privacy_agreed' => $privacyAgreed,
                    'source' => ParticipantSource::Import,
                ]);
                $participant->save();

                if ($wasExisting) {
                    $updated++;
                } else {
                    $imported++;
                }
            }
        });

        fclose($handle);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array{full_name: int, employee_number: int, privacy: int|null}
     */
    private function mapColumns(array $header): array
    {
        $normalized = array_map(
            fn (?string $column): string => strtolower(trim((string) $column)),
            $header,
        );

        $fullName = $this->findColumnIndex($normalized, [
            'full name',
            'fullname',
            'employee name',
        ]);
        $employeeNumber = $this->findColumnIndex($normalized, [
            'id number',
            'employee no.',
            'employee no',
            'employee number',
            'emp no',
            'emp no.',
        ]);
        $privacy = $this->findColumnIndex($normalized, [
            'privacy notice',
            'privacy',
            'privacy consent',
        ]);

        if ($fullName === null || $employeeNumber === null) {
            throw new InvalidArgumentException(
                'CSV must include Full Name and ID Number (or Employee No.) columns.',
            );
        }

        return [
            'full_name' => $fullName,
            'employee_number' => $employeeNumber,
            'privacy' => $privacy,
        ];
    }

    /**
     * @param  array<int, string>  $normalized
     * @param  array<int, string>  $aliases
     */
    private function findColumnIndex(array $normalized, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $normalized, true);

            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parsePrivacyConsent(string $value): ?bool
    {
        $normalized = strtolower(trim($value));

        return match (true) {
            in_array($normalized, ['agree', 'agreed', 'yes', 'y', 'true', '1'], true) => true,
            in_array($normalized, ['disagree', 'disagreed', 'no', 'n', 'false', '0'], true) => false,
            default => null,
        };
    }
}
