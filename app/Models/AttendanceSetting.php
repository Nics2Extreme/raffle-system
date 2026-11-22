<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $spreadsheet_id
 * @property string $sheet_range
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['spreadsheet_id', 'sheet_range'])]
class AttendanceSetting extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sheet_range' => 'Attendance!A:C',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'spreadsheet_id' => config('services.google_sheets.spreadsheet_id'),
            'sheet_range' => config('services.google_sheets.range', 'Attendance!A:C'),
        ]);
    }
}
