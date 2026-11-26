<?php

use App\Enums\PrizeType;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function makePrizesXlsx(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'prizes').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'prizes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('operators can import prizes from an xlsx file', function () {
    $user = User::factory()->create();

    $file = makePrizesXlsx([
        ['Prize Name', 'Prize Quantity', 'Prize Type'],
        ['Gift Card', 5, 'minor'],
        ['TV', 1, 'major'],
        ['Broken', 0, 'minor'],
    ]);

    $this->actingAs($user)
        ->post(route('participants.prizes.import'), ['file' => $file])
        ->assertRedirect(route('participants.index'));

    expect(Prize::query()->count())->toBe(2)
        ->and(Prize::query()->where('name', 'Gift Card')->first())
        ->quantity->toBe(5)
        ->remaining->toBe(5)
        ->type->toBe(PrizeType::Minor)
        ->and(Prize::query()->where('name', 'TV')->first())
        ->type->toBe(PrizeType::Major);
});

test('reimporting prizes preserves already awarded units', function () {
    $user = User::factory()->create();

    Prize::factory()->create([
        'name' => 'Gift Card',
        'quantity' => 5,
        'remaining' => 2,
        'type' => PrizeType::Minor,
    ]);

    $file = makePrizesXlsx([
        ['Prize Name', 'Prize Quantity', 'Prize Type'],
        ['Gift Card', 8, 'minor'],
    ]);

    $this->actingAs($user)
        ->post(route('participants.prizes.import'), ['file' => $file])
        ->assertRedirect(route('participants.index'));

    expect(Prize::query()->where('name', 'Gift Card')->first())
        ->quantity->toBe(8)
        ->remaining->toBe(5);
});
