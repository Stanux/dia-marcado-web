<?php

declare(strict_types=1);

namespace App\Services\Guests;

use App\Models\WeddingGuest;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeddingGuestSpreadsheetImportService
{
    /**
     * @var array<int, string>
     */
    private const TEMPLATE_HEADERS = [
        'Nome',
        'Apelido',
        'E-mail',
        'Telefone',
        'Contato Principal',
        'Lado',
        'Criança',
    ];

    /**
     * @var array<int, string>
     */
    private const REQUIRED_NORMALIZED_HEADERS = [
        'nome',
        'apelido',
        'e_mail',
        'telefone',
        'contato_principal',
        'lado',
        'crianca',
    ];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');
        $sheet->fromArray([
            'Ana Oliveira',
            'Ana',
            'ana.oliveira@example.com',
            '(11) 99999-0001',
            '',
            'Noiva',
            'Não',
        ], null, 'A2');
        $sheet->fromArray([
            'Bruno Oliveira',
            'Bruno',
            'bruno.oliveira@example.com',
            '(11) 99999-0002',
            'Ana Oliveira',
            'Noivo',
            'Não',
        ], null, 'A3');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'modelo-importacao-convidados.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{
     *     processed_rows: int,
     *     created: int,
     *     duplicates: int,
     *     errors: int,
     *     rows: array<int, array{row: int, status: string, message: string, name: string}>
     * }
     */
    public function import(string $path, string $weddingId, ?string $createdBy = null): array
    {
        [$headerIndexes, $rows] = $this->readSpreadsheet($path);

        $existingNameEmailDuplicateKeys = $this->loadExistingNameEmailDuplicateKeys($weddingId);
        $existingDetailedDuplicateKeys = $this->loadExistingDetailedDuplicateKeys($weddingId);
        [$knownPrimaryContacts, $ambiguousPrimaryContactNames] = $this->loadKnownPrimaryContacts($weddingId);

        $processedNameEmailDuplicateKeys = [];
        $processedDetailedDuplicateKeys = [];
        $resultRows = [];
        $processedRows = 0;
        $created = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $rowNumber = $row['row_number'];
            $values = $row['values'];

            if ($this->isRowEmpty($values)) {
                continue;
            }

            $processedRows++;

            $name = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'nome'));
            $nickname = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'apelido'));
            $email = $this->normalizeEmail($this->valueFromRow($values, $headerIndexes, 'e_mail'));
            $phone = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'telefone'));
            $normalizedPhone = $this->normalizePhone($phone);
            $primaryContactName = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'contato_principal'));
            $sideRaw = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'lado'));
            $isChildRaw = $this->cleanString($this->valueFromRow($values, $headerIndexes, 'crianca'));

            if ($name === null) {
                $errors++;
                $resultRows[] = $this->buildRowResult($rowNumber, 'error', 'Nome é obrigatório.', '');
                continue;
            }

            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors++;
                $resultRows[] = $this->buildRowResult($rowNumber, 'error', 'E-mail inválido.', $name);
                continue;
            }

            $side = $this->normalizeSide($sideRaw);
            if ($side === null) {
                $errors++;
                $resultRows[] = $this->buildRowResult($rowNumber, 'error', 'Lado inválido. Use Noiva, Noivo ou Ambos.', $name);
                continue;
            }

            $primaryContactId = null;
            if ($primaryContactName !== null) {
                $normalizedPrimaryContactName = $this->normalizeName($primaryContactName);

                if (isset($ambiguousPrimaryContactNames[$normalizedPrimaryContactName])) {
                    $errors++;
                    $resultRows[] = $this->buildRowResult(
                        $rowNumber,
                        'error',
                        "Contato Principal \"{$primaryContactName}\" está ambíguo (mais de um cadastro com esse nome).",
                        $name
                    );
                    continue;
                }

                if (!isset($knownPrimaryContacts[$normalizedPrimaryContactName])) {
                    $errors++;
                    $resultRows[] = $this->buildRowResult(
                        $rowNumber,
                        'error',
                        "Contato Principal \"{$primaryContactName}\" não encontrado. Ele precisa existir na plataforma ou estar em uma linha anterior da planilha.",
                        $name
                    );
                    continue;
                }

                $primaryContactId = $knownPrimaryContacts[$normalizedPrimaryContactName];
            }

            $nameEmailDuplicateKey = $this->buildNameEmailDuplicateKey($name, $email);
            if (
                $nameEmailDuplicateKey !== null
                && (
                    isset($existingNameEmailDuplicateKeys[$nameEmailDuplicateKey])
                    || isset($processedNameEmailDuplicateKeys[$nameEmailDuplicateKey])
                )
            ) {
                $duplicates++;
                $resultRows[] = $this->buildRowResult($rowNumber, 'duplicate', 'Convidado duplicado (Nome + E-mail).', $name);
                continue;
            }

            $detailedDuplicateKey = $this->buildDetailedDuplicateKey($name, $email, $normalizedPhone, $primaryContactId);
            if (
                isset($existingDetailedDuplicateKeys[$detailedDuplicateKey])
                || isset($processedDetailedDuplicateKeys[$detailedDuplicateKey])
            ) {
                $duplicates++;
                $resultRows[] = $this->buildRowResult(
                    $rowNumber,
                    'duplicate',
                    'Convidado duplicado (Nome + E-mail + Telefone + Contato Principal).',
                    $name
                );
                continue;
            }

            $guest = WeddingGuest::withoutGlobalScopes()->create([
                'wedding_id' => $weddingId,
                'created_by' => $createdBy,
                'primary_contact_id' => $primaryContactId,
                'name' => $name,
                'nickname' => $nickname,
                'email' => $email,
                'phone' => $phone,
                'side' => $side,
                'status' => 'pending',
                'is_child' => $this->normalizeBoolean($isChildRaw),
                'is_active' => true,
            ]);

            $created++;
            $resultRows[] = $this->buildRowResult($rowNumber, 'created', 'Convidado importado com sucesso.', $name);

            if ($nameEmailDuplicateKey !== null) {
                $processedNameEmailDuplicateKeys[$nameEmailDuplicateKey] = true;
            }

            $processedDetailedDuplicateKeys[$detailedDuplicateKey] = true;

            if ($primaryContactId === null) {
                $this->registerKnownPrimaryContact(
                    $knownPrimaryContacts,
                    $ambiguousPrimaryContactNames,
                    $this->normalizeName($guest->name),
                    (string) $guest->id
                );
            }
        }

        return [
            'processed_rows' => $processedRows,
            'created' => $created,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'rows' => $resultRows,
        ];
    }

    /**
     * @return array{0: array<string, int>, 1: array<int, array{row_number: int, values: array<int, mixed>}>}
     */
    private function readSpreadsheet(string $path): array
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException('Arquivo não encontrado para importação.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($rows === []) {
            throw new InvalidArgumentException('A planilha está vazia.');
        }

        $headerRow = $rows[0] ?? [];
        $headerIndexes = $this->buildHeaderIndexes($headerRow);

        $dataRows = [];
        foreach (array_slice($rows, 1) as $index => $rowValues) {
            $dataRows[] = [
                'row_number' => $index + 2,
                'values' => $rowValues,
            ];
        }

        return [$headerIndexes, $dataRows];
    }

    /**
     * @param array<int, mixed> $headerRow
     * @return array<string, int>
     */
    private function buildHeaderIndexes(array $headerRow): array
    {
        $indexMap = [];

        foreach ($headerRow as $index => $headerValue) {
            $normalized = $this->normalizeHeader((string) $headerValue);
            if ($normalized !== '' && !array_key_exists($normalized, $indexMap)) {
                $indexMap[$normalized] = $index;
            }
        }

        $missing = array_values(array_filter(
            self::REQUIRED_NORMALIZED_HEADERS,
            static fn (string $required): bool => !array_key_exists($required, $indexMap)
        ));

        if ($missing !== []) {
            $expected = implode(', ', self::TEMPLATE_HEADERS);
            throw new InvalidArgumentException(
                'A planilha não segue o modelo esperado. Campos obrigatórios: ' . $expected . '.'
            );
        }

        return $indexMap;
    }

    private function normalizeHeader(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    /**
     * @param array<int, mixed> $rowValues
     * @param array<string, int> $headerIndexes
     */
    private function valueFromRow(array $rowValues, array $headerIndexes, string $key): mixed
    {
        if (!array_key_exists($key, $headerIndexes)) {
            return null;
        }

        $index = $headerIndexes[$key];
        return $rowValues[$index] ?? null;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }

    private function normalizeName(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ');
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $text = $this->cleanString($value);
        if ($text === null) {
            return null;
        }

        return Str::lower($text);
    }

    private function normalizeSide(?string $value): ?string
    {
        if ($value === null) {
            return 'both';
        }

        $normalized = (string) Str::of($value)->trim()->ascii()->lower();

        return match ($normalized) {
            'noiva', 'bride' => 'bride',
            'noivo', 'groom' => 'groom',
            'ambos', 'both' => 'both',
            default => null,
        };
    }

    private function normalizeBoolean(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = (string) Str::of($value)->trim()->ascii()->lower();
        return in_array($normalized, ['1', 'sim', 's', 'true', 'yes', 'y'], true);
    }

    private function normalizePhone(mixed $value): ?string
    {
        $text = $this->cleanString($value);
        if ($text === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $text);
        if ($digits === null || $digits === '') {
            return null;
        }

        return $digits;
    }

    private function buildNameEmailDuplicateKey(string $name, ?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return $this->normalizeName($name) . '|' . $email;
    }

    /**
     * @return array<string, bool>
     */
    private function loadExistingNameEmailDuplicateKeys(string $weddingId): array
    {
        $keys = [];

        WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->whereNotNull('email')
            ->get(['name', 'email'])
            ->each(function (WeddingGuest $guest) use (&$keys): void {
                $email = $this->normalizeEmail($guest->email);
                if ($email === null) {
                    return;
                }

                $keys[$this->normalizeName((string) $guest->name) . '|' . $email] = true;
            });

        return $keys;
    }

    private function buildDetailedDuplicateKey(
        string $name,
        ?string $email,
        ?string $normalizedPhone,
        ?string $primaryContactId
    ): string {
        return implode('|', [
            $this->normalizeName($name),
            $email ?? '__null__',
            $normalizedPhone ?? '__null__',
            $primaryContactId ?? '__null__',
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private function loadExistingDetailedDuplicateKeys(string $weddingId): array
    {
        $keys = [];

        WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->get(['name', 'email', 'phone', 'primary_contact_id'])
            ->each(function (WeddingGuest $guest) use (&$keys): void {
                $keys[$this->buildDetailedDuplicateKey(
                    (string) $guest->name,
                    $this->normalizeEmail($guest->email),
                    $this->normalizePhone($guest->phone),
                    $guest->primary_contact_id ? (string) $guest->primary_contact_id : null
                )] = true;
            });

        return $keys;
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, bool>}
     */
    private function loadKnownPrimaryContacts(string $weddingId): array
    {
        $contacts = [];
        $ambiguous = [];

        WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->whereNull('primary_contact_id')
            ->get(['id', 'name'])
            ->each(function (WeddingGuest $guest) use (&$contacts, &$ambiguous): void {
                $this->registerKnownPrimaryContact(
                    $contacts,
                    $ambiguous,
                    $this->normalizeName((string) $guest->name),
                    (string) $guest->id
                );
            });

        return [$contacts, $ambiguous];
    }

    /**
     * @param array<string, string> $contacts
     * @param array<string, bool> $ambiguous
     */
    private function registerKnownPrimaryContact(array &$contacts, array &$ambiguous, string $normalizedName, string $guestId): void
    {
        if ($normalizedName === '') {
            return;
        }

        if (isset($ambiguous[$normalizedName])) {
            return;
        }

        if (!isset($contacts[$normalizedName])) {
            $contacts[$normalizedName] = $guestId;
            return;
        }

        if ($contacts[$normalizedName] !== $guestId) {
            unset($contacts[$normalizedName]);
            $ambiguous[$normalizedName] = true;
        }
    }

    /**
     * @param array<int, mixed> $values
     */
    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{row: int, status: string, message: string, name: string}
     */
    private function buildRowResult(int $row, string $status, string $message, string $name): array
    {
        return [
            'row' => $row,
            'status' => $status,
            'message' => $message,
            'name' => $name,
        ];
    }
}
