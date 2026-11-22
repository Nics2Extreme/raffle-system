<?php

namespace App\Services\GoogleSheets;

use App\Contracts\ReadsGoogleSheets;
use Google\Client;
use Google\Service\Sheets;
use RuntimeException;

class GoogleSheetsReader implements ReadsGoogleSheets
{
    /**
     * @return array<int, array<int, string|null>>
     */
    public function values(string $spreadsheetId, string $range): array
    {
        $client = new Client;
        $client->setApplicationName(config('app.name'));
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($this->credentials());

        $service = new Sheets($client);
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues() ?? [];

        return array_map(
            fn (array $row): array => array_map(
                fn (mixed $cell): ?string => $cell === null ? null : (string) $cell,
                $row,
            ),
            $values,
        );
    }

    /**
     * @return array<string, mixed>|string
     */
    private function credentials(): array|string
    {
        $json = config('services.google_sheets.credentials_json');

        if (is_string($json) && trim($json) !== '') {
            $decoded = json_decode($json, true);

            if (! is_array($decoded)) {
                throw new RuntimeException('GOOGLE_SHEETS_CREDENTIALS_JSON is not valid JSON.');
            }

            return $decoded;
        }

        $path = config('services.google_sheets.credentials');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new RuntimeException(
                'Google Sheets credentials are not configured. Set GOOGLE_SHEETS_CREDENTIALS or GOOGLE_SHEETS_CREDENTIALS_JSON.',
            );
        }

        return $path;
    }
}
