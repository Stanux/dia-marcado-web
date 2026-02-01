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
    public bool $showCreateAlbum = false;
    public string $newAlbumName = '';
    public string $newAlbumType = '';

    public int $successCount = 0;
    public int $errorCount = 0;
    public array $uploadErrors = [];
    public bool $isUploading = false;

    // Listeners for Livewire events
    protected $listeners = ['filesUploaded' => 'handleFilesUploaded'];

    // Rules for validation
    protected $rules = [
        'files.*' => 'file|max:10240|mimes:jpeg,jpg,png,gif,webp,mp4,webm',
        'albumId' => 'required|exists:albums,id',
    ];

    public function mount(): void
    {
        $this->albumId = null;
        $this->files = [];
        $this->showCreateAlbum = false;
        $this->newAlbumName = '';
        $this->newAlbumType = '';
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

    public function getAlbumTypeOptions(): array
    {
        try {
            // Garantir que os tipos existam
            $this->ensureAlbumTypesExist();
            
            return \App\Models\AlbumType::all()
                ->mapWithKeys(fn ($type) => [
                    $type->slug => $type->name
                ])
                ->toArray();
        } catch (\Exception $e) {
            // Se não conseguir carregar os tipos, retorna tipos padrão
            return [
                'pre_casamento' => 'Pré Casamento',
                'pos_casamento' => 'Pós Casamento', 
                'uso_site' => 'Uso do Site'
            ];
        }
    }

    public function toggleCreateAlbum(): void
    {
        $this->showCreateAlbum = !$this->showCreateAlbum;
        if (!$this->showCreateAlbum) {
            $this->newAlbumName = '';
            $this->newAlbumType = '';
        }
    }

    public function createAlbum(): void
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

        // Validação manual mais robusta
        if (empty($this->newAlbumName)) {
            Notification::make()
                ->title('Nome obrigatório')
                ->body('Digite um nome para o álbum.')
                ->warning()
                ->send();
            return;
        }

        if (empty($this->newAlbumType)) {
            Notification::make()
                ->title('Tipo obrigatório')
                ->body('Selecione um tipo para o álbum.')
                ->warning()
                ->send();
            return;
        }

        try {
            // Garantir que os tipos de álbum existam
            $this->ensureAlbumTypesExist();
            
            $albumType = \App\Models\AlbumType::where('slug', $this->newAlbumType)->first();
            
            if (!$albumType) {
                Notification::make()
                    ->title('Tipo inválido')
                    ->body('Tipo de álbum não encontrado.')
                    ->danger()
                    ->send();
                return;
            }
            
            $album = Album::create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
                'name' => $this->newAlbumName,
                'description' => '',
            ]);

            $this->albumId = $album->id;
            $this->showCreateAlbum = false;
            $this->newAlbumName = '';
            $this->newAlbumType = '';

            Notification::make()
                ->title('Álbum criado!')
                ->body("Álbum '{$album->name}' criado com sucesso.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao criar álbum')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function ensureAlbumTypesExist(): void
    {
        $types = [
            ['slug' => 'pre_casamento', 'name' => 'Pré Casamento', 'description' => 'Fotos e vídeos do período antes do casamento'],
            ['slug' => 'pos_casamento', 'name' => 'Pós Casamento', 'description' => 'Fotos e vídeos do casamento e lua de mel'],
            ['slug' => 'uso_site', 'name' => 'Uso do Site', 'description' => 'Mídias para uso no site de casamento']
        ];

        foreach ($types as $type) {
            \App\Models\AlbumType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    public function updatedFiles(): void
    {
        // Force refresh of the component state
        $this->dispatch('files-updated');
        
        // Log detalhado para debug
        \Log::info('Files updated - DETAILED:', [
            'files_count' => count($this->files ?? []),
            'files_type' => gettype($this->files),
            'files_empty' => empty($this->files),
            'files_is_array' => is_array($this->files),
            'files_content' => array_map(function($file) {
                if ($file instanceof UploadedFile) {
                    return [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'valid' => $file->isValid(),
                        'path' => $file->getRealPath(),
                    ];
                }
                return [
                    'type' => gettype($file), 
                    'value' => is_string($file) ? $file : 'non-string'
                ];
            }, $this->files ?? [])
        ]);

        // Validate files when they are updated, but only if they exist
        if (!empty($this->files) && is_array($this->files)) {
            try {
                $this->validate([
                    'files.*' => [
                        'file',
                        'max:10240', // 10MB
                        'mimes:jpeg,jpg,png,gif,webp,mp4,webm',
                    ],
                ]);
            } catch (\Exception $e) {
                // Log error but don't block the upload process
                \Log::warning('File validation warning: ' . $e->getMessage());
            }
        }
        
        // Force component re-render
        $this->render();
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
        $this->reset(['files']);
    }

    public function debugFiles(): void
    {
        $debugInfo = [
            'files_count' => count($this->files ?? []),
            'files_type' => gettype($this->files),
            'files_empty' => empty($this->files),
            'files_is_array' => is_array($this->files),
            'files_content' => array_map(function($file) {
                if ($file instanceof UploadedFile) {
                    return [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'valid' => $file->isValid(),
                        'error' => $file->getError(),
                        'path' => $file->getRealPath(),
                    ];
                }
                return ['type' => gettype($file), 'value' => $file];
            }, $this->files ?? [])
        ];
        
        \Log::info('Files debug:', $debugInfo);
        
        // Show notification with debug info
        Notification::make()
            ->title('Debug Files')
            ->body('Arquivos: ' . count($this->files ?? []) . ' | Tipo: ' . gettype($this->files) . ' | Vazio: ' . (empty($this->files) ? 'Sim' : 'Não'))
            ->info()
            ->send();
    }

    public function handleFilesUploaded($files): void
    {
        \Log::info('Files uploaded event received:', [
            'files' => $files,
            'count' => count($files ?? [])
        ]);
        
        $this->files = $files;
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

        // Validar que um álbum foi selecionado (obrigatório conforme Requisito 2.3)
        if (empty($this->albumId)) {
            Notification::make()
                ->title('Álbum obrigatório')
                ->body('Selecione um álbum para associar as mídias.')
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
                    'album_id' => $this->albumId, // Agora sempre obrigatório
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
