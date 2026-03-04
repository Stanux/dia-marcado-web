<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\WeddingGuestResource;
use App\Services\Guests\WeddingGuestSpreadsheetImportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeddingGuestImportTemplateController extends Controller
{
    public function __invoke(WeddingGuestSpreadsheetImportService $importService): StreamedResponse
    {
        abort_unless(WeddingGuestResource::canCreate(), 403);

        return $importService->downloadTemplate();
    }
}
