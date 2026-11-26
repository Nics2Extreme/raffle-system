<?php

namespace App\Actions;

use App\Enums\PrizeType;
use App\Models\Prize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPrizesAction
{
    /**
     * @return array{imported: int, updated: int, skipped: int}
     */
    public function handle(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Unable to read the uploaded spreadsheet.', 0, $exception);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ($rows === []) {
            throw new InvalidArgumentException('The spreadsheet is empty.');
        }

        $header = array_shift($rows);

        if (! is_array($header)) {
            throw new InvalidArgumentException('The spreadsheet is empty.');
        }

        $columns = $this->mapColumns($header);
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $columns, &$imported, &$updated, &$skipped): void {
            foreach ($rows as $row) {
                if (! is_array($row) || $this->rowIsBlank($row)) {
                    continue;
                }

                $name = trim((string) ($row[$columns['name']] ?? ''));
                $quantity = $this->parseQuantity($row[$columns['quantity']] ?? null);
                $type = $this->parseType((string) ($row[$columns['type']] ?? ''));

                if ($name === '' || $quantity === null || $type === null) {
                    $skipped++;

                    continue;
                }

                $prize = Prize::query()->firstOrNew(['name' => $name]);
                $wasExisting = $prize->exists;

                $alreadyAwarded = $wasExisting
                    ? max(0, $prize->quantity - $prize->remaining)
                    : 0;

                $prize->fill([
                    'name' => $name,
                    'quantity' => $quantity,
                    'remaining' => max(0, $quantity - $alreadyAwarded),
                    'type' => $type,
                ]);
                $prize->save();

                if ($wasExisting) {
                    $updated++;
                } else {
                    $imported++;
                }
            }
        });

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array{name: int, quantity: int, type: int}
     */
    private function mapColumns(array $header): array
    {
        $normalized = array_map(
            fn (mixed $column): string => strtolower(trim((string) $column)),
            $header,
        );

        $name = $this->findColumnIndex($normalized, [
            'prize name',
            'name',
            'prize',
        ]);
        $quantity = $this->findColumnIndex($normalized, [
            'prize quantity',
            'quantity',
            'qty',
            'count',
        ]);
        $type = $this->findColumnIndex($normalized, [
            'prize type',
            'type',
            'tier',
        ]);

        if ($name === null || $quantity === null || $type === null) {
            throw new InvalidArgumentException(
                'Spreadsheet must include Prize Name, Prize Quantity, and Prize Type columns.',
            );
        }

        return [
            'name' => $name,
            'quantity' => $quantity,
            'type' => $type,
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
     * @param  array<int, mixed>  $row
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

    private function parseQuantity(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $quantity = (int) $value;

        return $quantity >= 1 ? $quantity : null;
    }

    private function parseType(string $value): ?PrizeType
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'major', 'grand', 'big' => PrizeType::Major,
            'minor', 'small', 'consolation' => PrizeType::Minor,
            default => null,
        };
    }
}
