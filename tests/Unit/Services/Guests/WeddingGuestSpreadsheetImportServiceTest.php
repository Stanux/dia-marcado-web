<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Guests;

use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Services\Guests\WeddingGuestSpreadsheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class WeddingGuestSpreadsheetImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_guests_and_links_primary_contact_from_previous_row(): void
    {
        $wedding = Wedding::factory()->create();
        $service = app(WeddingGuestSpreadsheetImportService::class);

        $path = $this->createSpreadsheet([
            ['Nome', 'Apelido', 'E-mail', 'Telefone', 'Contato Principal', 'Lado', 'Status', 'Criança'],
            ['Ana Oliveira', 'Ana', 'ana@example.com', '(11) 99999-1111', '', 'Noiva', 'Pendente', 'Não'],
            ['Bruno Oliveira', 'Bruno', 'bruno@example.com', '(11) 99999-2222', 'Ana Oliveira', 'Noivo', 'Confirmado', 'Não'],
        ]);

        $result = $service->import($path, (string) $wedding->id, null);

        $this->assertSame(2, $result['processed_rows']);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertSame(0, $result['errors']);

        $ana = WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('name', 'Ana Oliveira')
            ->firstOrFail();

        $bruno = WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('name', 'Bruno Oliveira')
            ->firstOrFail();

        $this->assertNull($ana->primary_contact_id);
        $this->assertSame((string) $ana->id, (string) $bruno->primary_contact_id);
    }

    public function test_import_marks_duplicate_when_name_and_email_already_exist(): void
    {
        $wedding = Wedding::factory()->create();
        $service = app(WeddingGuestSpreadsheetImportService::class);

        WeddingGuest::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Ana Oliveira',
            'email' => 'ana@example.com',
            'status' => 'pending',
            'side' => 'both',
            'is_child' => false,
            'is_active' => true,
        ]);

        $path = $this->createSpreadsheet([
            ['Nome', 'Apelido', 'E-mail', 'Telefone', 'Contato Principal', 'Lado', 'Status', 'Criança'],
            ['Ana Oliveira', 'Ana', 'ana@example.com', '(11) 99999-1111', '', 'Noiva', 'Pendente', 'Não'],
        ]);

        $result = $service->import($path, (string) $wedding->id, null);

        $this->assertSame(1, $result['processed_rows']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['duplicates']);
        $this->assertSame(0, $result['errors']);
    }

    public function test_import_reports_error_when_primary_contact_not_previously_available(): void
    {
        $wedding = Wedding::factory()->create();
        $service = app(WeddingGuestSpreadsheetImportService::class);

        $path = $this->createSpreadsheet([
            ['Nome', 'Apelido', 'E-mail', 'Telefone', 'Contato Principal', 'Lado', 'Status', 'Criança'],
            ['Bruno Oliveira', 'Bruno', 'bruno@example.com', '(11) 99999-2222', 'Ana Oliveira', 'Noivo', 'Confirmado', 'Não'],
            ['Ana Oliveira', 'Ana', 'ana@example.com', '(11) 99999-1111', '', 'Noiva', 'Pendente', 'Não'],
        ]);

        $result = $service->import($path, (string) $wedding->id, null);

        $this->assertSame(2, $result['processed_rows']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertSame(1, $result['errors']);

        $errorRows = collect($result['rows'])
            ->where('status', 'error')
            ->all();

        $this->assertNotEmpty($errorRows);
        $this->assertStringContainsString('Contato Principal', $errorRows[0]['message']);
    }

    public function test_import_marks_duplicate_when_name_email_phone_and_primary_contact_already_exist(): void
    {
        $wedding = Wedding::factory()->create();
        $service = app(WeddingGuestSpreadsheetImportService::class);

        $primaryContact = WeddingGuest::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Carlos Silva',
            'status' => 'pending',
            'side' => 'both',
            'is_child' => false,
            'is_active' => true,
        ]);

        WeddingGuest::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'primary_contact_id' => $primaryContact->id,
            'name' => 'Ana Oliveira',
            'email' => null,
            'phone' => '(11) 99999-1111',
            'status' => 'pending',
            'side' => 'both',
            'is_child' => false,
            'is_active' => true,
        ]);

        $path = $this->createSpreadsheet([
            ['Nome', 'Apelido', 'E-mail', 'Telefone', 'Contato Principal', 'Lado', 'Status', 'Criança'],
            ['Ana Oliveira', 'Ana', '', '11 99999-1111', 'Carlos Silva', 'Noiva', 'Pendente', 'Não'],
        ]);

        $result = $service->import($path, (string) $wedding->id, null);

        $this->assertSame(1, $result['processed_rows']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['duplicates']);
        $this->assertSame(0, $result['errors']);
        $this->assertStringContainsString(
            'Nome + E-mail + Telefone + Contato Principal',
            $result['rows'][0]['message']
        );
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function createSpreadsheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'wedding-guest-import-');
        if ($path === false) {
            throw new \RuntimeException('Não foi possível criar arquivo temporário de teste.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
