<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use App\Models\SiteLayout;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $wedding = $user->currentWedding;

        if (!$wedding) {
            throw new \Exception('Nenhum casamento selecionado.');
        }

        // Get or create site layout
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => Str::uuid()->toString(),
                'draft_content' => json_encode(['sections' => []]),
            ]);
        }

        // Process uploaded file
        $uploadedFile = $data['file'] ?? null;
        
        if ($uploadedFile) {
            // Get file info from the uploaded path
            $disk = Storage::disk('public');
            $filePath = $uploadedFile;
            
            if ($disk->exists($filePath)) {
                $fileSize = $disk->size($filePath);
                $mimeType = $disk->mimeType($filePath);
                $originalName = basename($filePath);
                
                // Generate UUID filename
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $newFilename = Str::uuid()->toString() . '.' . $extension;
                $newPath = 'sites/' . $wedding->id . '/media/' . $newFilename;
                
                // Move file to final location
                $disk->move($filePath, $newPath);
                
                $data['path'] = $newPath;
                $data['original_name'] = $originalName;
                $data['size'] = $fileSize;
                $data['mime_type'] = $mimeType;
                $data['disk'] = 'public';
                $data['status'] = 'completed';
                $data['variants'] = [];
            }
        }

        $data['wedding_id'] = $wedding->id;
        $data['site_layout_id'] = $siteLayout->id;
        
        unset($data['file']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Mídia enviada com sucesso!';
    }
}
