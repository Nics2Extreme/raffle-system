<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'spreadsheet_id' => ['nullable', 'string', 'max:255'],
            'sheet_range' => ['nullable', 'string', 'max:255'],
        ];
    }
}
