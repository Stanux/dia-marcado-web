<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Contracts\Media\BatchUploadServiceInterface;
use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Notifications\Notification;

/**
 * Livewire component for the Media Gallery.
 * 
 * Blade/Livewire replacement for the Vue.js MediaScreen component.
 * Handles album management, media upload, selection, and operations.
 */
class MediaGalleryBlade extends Component
{
    use WithFileUploads;

    // Album state
    public ?string $selectedAlbumId = null;
    
    // Media selection state
    public array $selectedMediaIds = [];
    
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public bool $showMoveModal = false;
    public bool $showDeleteMediaModal = false;
    
    // Form data
    public string $albumName = '';
    public string $albumType = 'uso_site';
    public ?string $editingAlbumId = null;
    public ?string $deletingAlbumId = null;
    
    // Grid size
    public string $gridSize = 'medium';

    // Rename mode
    public bool $isRenameMode = false;
    public array $renameNames = [];
    
    // Upload state
    public $uploadFiles = [];
    public bool $isUploading = false;
    public int $uploadProgress = 0;

    protected AlbumManagementServiceInterface $albumService;
    protected BatchUploadServiceInterface $batchUploadService;

    public function boot(
        AlbumManagementServiceInterface $albumService,
        BatchUploadServiceInterface $batchUploadService
    ): void {
        $this->albumService = $albumService;
        $this->batchUploadService = $batchUploadService;
    }

    public function mount(): void
    {
        // Select first album by default if exists
        $albums = $this->getAlbums();
        if (count($albums) > 0 && $this->selectedAlbumId === null) {
            $this->selectedAlbumId = $albums[0]['id'];
        }
    }

    #[Computed]
    public function albums(): array
    {
        return $this->getAlbums();
    }

    #[Computed]
    public function selectedAlbum(): ?array
    {
        if (!$this->selectedAlbumId) {
            return null;
        }

        $albums = $this->getAlbums();
        foreach ($albums as $album) {
            if ($album['id'] === $this->selectedAlbumId) {
                return $album;
            }
        }

        return null;
    }

    #[Computed]
    public function albumTypes(): array
    {
        return AlbumType::orderBy('name')->get()->map(fn ($type) => [
            'slug' => $type->slug,
            'name' => $type->name,
        ])->toArray();
    }

    #[Computed]
    public function otherAlbums(): array
    {
        return array_filter($this->getAlbums(), fn ($album) => $album['id'] !== $this->selectedAlbumId);
    }

    private function getAlbums(): array
    {
        return Album::withCount('media')
            ->with(['media' => function ($query) {
                $query->where('status', 'completed')
                    ->orderBy('created_at', 'desc');
            }, 'albumType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($album) {
                return [
                    'id' => $album->id,
                    'name' => $album->name,
                    'type' => $album->albumType?->slug ?? 'uso_site',
                    'type_name' => $album->albumType?->name ?? 'Uso do Site',
                    'description' => $album->description,
                    'media_count' => $album->media_count,
                    'media' => $album->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'filename' => $media->original_name,
                            'type' => str_starts_with($media->mime_type, 'image/') ? 'image' : 'video',
                            'mime_type' => $media->mime_type,
                            'size' => $media->size,
                            'url' => $media->getUrl(),
                            'thumbnail_url' => $media->getVariantUrl('thumbnail') ?? $media->getUrl(),
                            'created_at' => $media->created_at->toISOString(),
                        ];
                    })->toArray(),
                    'created_at' => $album->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    // Album Selection
    public function selectAlbum(string $albumId): void
    {
        $this->selectedAlbumId = $albumId;
        $this->cancelRenameMode();
        $this->clearSelection();
    }

    // Album CRUD Operations
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
        $this->dispatch('open-modal', id: 'create-edit-album-modal');
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('close-modal', id: 'create-edit-album-modal');
    }

    public function createAlbum(): void
    {
        $this->validate([
            'albumName' => 'required|string|max:255',
            'albumType' => 'required|string|exists:album_types,slug',
        ]);

        try {
            $albumType = AlbumType::where('slug', $this->albumType)->first();
            
            $album = Album::create([
                'name' => $this->albumName,
                'album_type_id' => $albumType?->id,
                'wedding_id' => auth()->user()?->currentWedding?->id,
            ]);

            $this->selectedAlbumId = $album->id;
            $this->closeCreateModal();
            
            Notification::make()
                ->title('Álbum criado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Failed to create album', ['error' => $e->getMessage()]);
            Notification::make()
                ->title('Erro ao criar álbum.')
                ->danger()
                ->send();
        }
    }

    public function openEditModal(string $albumId): void
    {
        $album = Album::with('albumType')->find($albumId);
        if ($album) {
            $this->editingAlbumId = $albumId;
            $this->albumName = $album->name;
            $this->albumType = $album->albumType?->slug ?? 'uso_site';
            $this->showEditModal = true;
            $this->dispatch('open-modal', id: 'create-edit-album-modal');
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAlbumId = null;
        $this->resetForm();
        $this->dispatch('close-modal', id: 'create-edit-album-modal');
    }

    public function updateAlbum(): void
    {
        $this->validate([
            'albumName' => 'required|string|max:255',
            'albumType' => 'required|string|exists:album_types,slug',
        ]);

        try {
            $album = Album::find($this->editingAlbumId);
            if ($album) {
                $albumType = AlbumType::where('slug', $this->albumType)->first();
                $album->update([
                    'name' => $this->albumName,
                    'album_type_id' => $albumType?->id,
                ]);
            }

            $this->closeEditModal();
            
            Notification::make()
                ->title('Álbum atualizado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Failed to update album', ['error' => $e->getMessage()]);
            Notification::make()
                ->title('Erro ao atualizar álbum.')
                ->danger()
                ->send();
        }
    }

    public function openDeleteModal(string $albumId): void
    {
        $this->deletingAlbumId = $albumId;
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', id: 'delete-album-modal');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingAlbumId = null;
        $this->dispatch('close-modal', id: 'delete-album-modal');
    }

    public function deleteAlbum(): void
    {
        try {
            $album = Album::find($this->deletingAlbumId);
            if ($album) {
                // Delete all media in the album
                foreach ($album->media as $media) {
                    $media->delete();
                }
                $album->delete();
            }

            // Select another album if the deleted one was selected
            if ($this->selectedAlbumId === $this->deletingAlbumId) {
                $albums = $this->getAlbums();
                $this->selectedAlbumId = count($albums) > 0 ? $albums[0]['id'] : null;
            }

            $this->closeDeleteModal();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Álbum excluído com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete album', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erro ao excluir álbum.',
            ]);
        }
    }

    // Media Selection
    public function toggleMediaSelection(string $mediaId): void
    {
        if (in_array($mediaId, $this->selectedMediaIds)) {
            $this->selectedMediaIds = array_values(array_diff($this->selectedMediaIds, [$mediaId]));
        } else {
            $this->selectedMediaIds[] = $mediaId;
        }
    }

    public function selectAllMedia(): void
    {
        $album = $this->selectedAlbum;
        if ($album) {
            $this->selectedMediaIds = array_column($album['media'], 'id');
        }
    }

    public function clearSelection(): void
    {
        $this->selectedMediaIds = [];
        $this->cancelRenameMode();
    }

    public function isMediaSelected(string $mediaId): bool
    {
        return in_array($mediaId, $this->selectedMediaIds);
    }

    // Media Operations
    public function openDeleteMediaModal(): void
    {
        if (count($this->selectedMediaIds) > 0) {
            $this->showDeleteMediaModal = true;
            $this->dispatch('open-modal', id: 'delete-media-modal');
        }
    }

    public function closeDeleteMediaModal(): void
    {
        $this->showDeleteMediaModal = false;
        $this->dispatch('close-modal', id: 'delete-media-modal');
    }

    public function deleteSelectedMedia(): void
    {
        try {
            $deletedCount = 0;
            foreach ($this->selectedMediaIds as $mediaId) {
                $media = SiteMedia::find($mediaId);
                if ($media) {
                    $media->delete();
                    $deletedCount++;
                }
            }

            $this->clearSelection();
            $this->closeDeleteMediaModal();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$deletedCount} mídia(s) excluída(s) com sucesso!",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete media', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erro ao excluir mídia(s).',
            ]);
        }
    }

    public function openMoveModal(): void
    {
        if (count($this->selectedMediaIds) > 0) {
            $this->showMoveModal = true;
            $this->dispatch('open-modal', id: 'move-media-modal');
        }
    }

    public function openRenameMode(): void
    {
        if (count($this->selectedMediaIds) === 0) {
            return;
        }

        $this->renameNames = [];

        $mediaItems = SiteMedia::query()
            ->whereIn('id', $this->selectedMediaIds)
            ->get(['id', 'original_name']);

        foreach ($mediaItems as $media) {
            $this->renameNames[$media->id] = pathinfo((string) $media->original_name, PATHINFO_FILENAME);
        }

        $this->isRenameMode = true;
    }

    public function cancelRenameMode(): void
    {
        $this->isRenameMode = false;
        $this->renameNames = [];
    }

    public function saveRenamedMedia(): void
    {
        if (!$this->isRenameMode || count($this->selectedMediaIds) === 0) {
            return;
        }

        $renamedCount = 0;

        foreach ($this->selectedMediaIds as $mediaId) {
            $typedName = trim((string) ($this->renameNames[$mediaId] ?? ''));
            if ($typedName === '') {
                continue;
            }

            $media = SiteMedia::query()->find($mediaId);
            if (!$media) {
                continue;
            }

            $baseName = $this->sanitizeBaseName($typedName);
            if ($baseName === '') {
                continue;
            }

            $extension = $this->resolveMediaExtension($media);
            $finalName = $this->generateUniqueMediaName($media, $baseName, $extension);

            if ($finalName === $media->original_name) {
                continue;
            }

            $media->update([
                'original_name' => $finalName,
            ]);

            $renamedCount++;
        }

        $this->cancelRenameMode();
        $this->clearSelection();

        Notification::make()
            ->title($renamedCount > 0 ? "{$renamedCount} mídia(s) renomeada(s) com sucesso!" : 'Nenhuma mídia foi renomeada.')
            ->success()
            ->send();
    }

    public function closeMoveModal(): void
    {
        $this->showMoveModal = false;
        $this->dispatch('close-modal', id: 'move-media-modal');
    }

    public function moveMediaToAlbum(string $targetAlbumId): void
    {
        try {
            $movedCount = 0;
            foreach ($this->selectedMediaIds as $mediaId) {
                $media = SiteMedia::find($mediaId);
                if ($media) {
                    $media->update(['album_id' => $targetAlbumId]);
                    $movedCount++;
                }
            }

            $this->clearSelection();
            $this->closeMoveModal();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$movedCount} mídia(s) movida(s) com sucesso!",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to move media', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erro ao mover mídia(s).',
            ]);
        }
    }

    // Upload
    public function updatedUploadFiles(): void
    {
        $this->uploadMedia();
    }

    public function uploadMedia(): void
    {
        if (!$this->selectedAlbumId || empty($this->uploadFiles)) {
            return;
        }

        $this->isUploading = true;

        try {
            foreach ($this->uploadFiles as $file) {
                $wedding = auth()->user()?->currentWedding;
                if (!$wedding) {
                    throw new \Exception('No wedding context');
                }

                // Store file first
                $path = $file->store("sites/{$wedding->id}/media", 'public');

                // Create media record with path
                SiteMedia::create([
                    'wedding_id' => $wedding->id,
                    'album_id' => $this->selectedAlbumId,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'status' => 'completed',
                ]);
            }

            $this->uploadFiles = [];
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Upload concluído com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload media', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erro ao fazer upload.',
            ]);
        } finally {
            $this->isUploading = false;
        }
    }

    // Grid Size
    public function setGridSize(string $size): void
    {
        $this->gridSize = $size;
    }

    // Helpers
    private function resetForm(): void
    {
        $this->albumName = '';
        $this->albumType = 'uso_site';
    }

    private function sanitizeBaseName(string $name): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($value === '') {
            return '';
        }

        $value = pathinfo($value, PATHINFO_FILENAME) ?: $value;
        $value = preg_replace('/[\/\\\\:*?"<>|]+/u', '', $value) ?? '';
        $value = trim($value, ". \t\n\r\0\x0B");

        return $value;
    }

    private function resolveMediaExtension(SiteMedia $media): string
    {
        $fromOriginalName = pathinfo((string) $media->original_name, PATHINFO_EXTENSION);
        if (is_string($fromOriginalName) && $fromOriginalName !== '') {
            return mb_strtolower($fromOriginalName);
        }

        $fromPath = pathinfo((string) $media->path, PATHINFO_EXTENSION);
        return is_string($fromPath) ? mb_strtolower($fromPath) : '';
    }

    private function generateUniqueMediaName(SiteMedia $media, string $baseName, string $extension): string
    {
        $index = 0;

        do {
            $candidateBase = $index === 0 ? $baseName : "{$baseName}_{$index}";
            $candidate = $extension !== '' ? "{$candidateBase}.{$extension}" : $candidateBase;

            $exists = SiteMedia::query()
                ->where('wedding_id', $media->wedding_id)
                ->where('album_id', $media->album_id)
                ->where('id', '!=', $media->id)
                ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($candidate)])
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $index++;
        } while (true);
    }

    public function render(): View
    {
        return view('livewire.media-gallery-blade');
    }
}
