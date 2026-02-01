<?php

namespace App\Filament\Pages;

use App\Models\Album;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

/**
 * Filament Page for batch media upload.
 * 
 * Allows uploading multiple files at once to an album.
 * Uses native Livewire file uploads for better multiple file support.
 * 
 * @Requirements: 1.1, 1.3, 6.1
 */
class MediaUpload extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Mídia';

    protected static ?string $navigationLabel = 'Enviar Mídias';

    protected static ?string $title = 'Enviar Múltiplas Mídias';

    protected static ?int $navigationSort = 35;

    protected static string $view = 'filament.pages.media-upload';

    // Native Livewire file upload property
    public $files = [];
    
    public ?string $albumId = null;

    public int $successCount = 0;
    public int $errorCount = 0;
    public array $uploadErrors = [];
    public bool $isUploading = false;

    public function mount(): void
    {
        $this->albumId = null;
        $this->files = [];
    }

    public function getAlbumOptions(): array
    {
        $user = auth()->user();
        $wedding = $user?->currentWedding;

        if (!$wedding) {
            return [];
        }

        return Album::where('wedding_id', $wedding->id)
            ->with('albumType')
            ->get()
            ->mapWithKeys(fn ($album) => [
                $album->id => $album->albumType->name . ' - ' . $album->name
            ])
            ->toArray();
    }

    public function updatedFiles(): void
    {
        // Validate files when they are updated
        $this->validateOnly('files.*', [
            'files.*' => [
                'file',
                'max:10240', // 10MB
                'mimes:jpeg,jpg,png,gif,webp,mp4,webm',
            ],
        ]);
    }

    public function removeFile(int $index): void
    {
        if (isset($this->files[$index])) {
            unset($this->files[$index]);
            $this->files = array_values($this->files);
        }
    }

    public function clearFiles(): void
    {
        $this->files = [];
    }

    public function upload(): void
    {
        $user = auth()->user();
        $wedding = $user?->currentWedding;

        if (!$wedding) {
            Notification::make()
                ->title('Erro')
                ->body('Nenhum casamento selecionado.')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->files)) {
            Notification::make()
                ->title('Nenhum arquivo')
                ->body('Selecione pelo menos um arquivo para enviar.')
                ->warning()
                ->send();
            return;
        }

        $this->isUploading = true;
        $this->successCount = 0;
        $this->errorCount = 0;
        $this->uploadErrors = [];

        // Get or create site layout
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => Str::uuid()->toString(),
                'draft_content' => json_encode(['sections' => []]),
            ]);
        }

        $disk = Storage::disk('public');

        foreach ($this->files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            try {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
                $fileSize = $file->getSize();

                // Generate UUID filename
                $newFilename = Str::uuid()->toString() . '.' . $extension;
                $newPath = 'media/' . $wedding->id . '/' . $newFilename;

                // Store file
                $file->storeAs('media/' . $wedding->id, $newFilename, 'public');

                // Create media record
                SiteMedia::create([
                    'wedding_id' => $wedding->id,
                    'site_layout_id' => $siteLayout->id,
                    'album_id' => $this->albumId ?: null,
                    'path' => $newPath,
                    'original_name' => $originalName,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                    'disk' => 'public',
                    'status' => 'completed',
                    'variants' => [],
                ]);

                $this->successCount++;

            } catch (\Exception $e) {
                $this->errorCount++;
                $this->uploadErrors[] = "Erro ao processar arquivo: " . $e->getMessage();
            }
        }

        // Clear files after upload
        $this->files = [];
        $this->isUploading = false;

        // Show notification
        if ($this->successCount > 0 && $this->errorCount === 0) {
            Notification::make()
                ->title('Upload concluído!')
                ->body("{$this->successCount} arquivo(s) enviado(s) com sucesso.")
                ->success()
                ->send();
        } elseif ($this->successCount > 0 && $this->errorCount > 0) {
            Notification::make()
                ->title('Upload parcial')
                ->body("{$this->successCount} arquivo(s) enviado(s), {$this->errorCount} erro(s).")
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Erro no upload')
                ->body("Nenhum arquivo foi enviado. {$this->errorCount} erro(s).")
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        $role = $user->roleIn($wedding);
        return in_array($role, ['couple', 'organizer']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
