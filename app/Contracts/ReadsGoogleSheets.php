<?php

namespace App\Contracts;

interface ReadsGoogleSheets
{
    /**
     * @return array<int, array<int, string|null>>
     */
    public function values(string $spreadsheetId, string $range): array;
}
