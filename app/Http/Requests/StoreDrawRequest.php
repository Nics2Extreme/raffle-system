<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'prize_id' => [
                'required',
                'integer',
                Rule::exists('prizes', 'id')->where(fn ($query) => $query->where('remaining', '>', 0)),
            ],
        ];
    }
}
