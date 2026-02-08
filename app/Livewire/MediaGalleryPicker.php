<?php

namespace App\Livewire;

use App\Models\Album;
use App\Models\SiteMedia;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

/**
 * Livewire component for media gallery picker with crop support.
 * 
 * This component provides a modal interface for selecting images from albums
 * and automatically cropping them if they exceed specified dimensions.
 */
class MediaGalleryPicker extends Component
{
    public bool $show = false;
    public ?int $maxWidth = null;
    public ?int $maxHeight = null;
    public ?string $selectedAlbumId = null;
    public array $albums = [];
    public array $albumMedia = [];
    public ?string $selectedImageUrl = null;
    
    // Crop state
    public bool $showCropModal = false;
    public ?array $imageToCrop = null;
    public array $cropArea = ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 0];

    protected $listeners = ['openMediaGallery' => 'open'];

    public function mount()
    {
        $this->loadAlbums();
    }

    public function open($maxWidth = null, $maxHeight = null)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->show = true;
        $this->selectedAlbumId = null;
        $this->albumMedia = [];
        $this->loadAlbums();
    }

    public function close()
    {
        $this->show = false;
        $this->selectedAlbumId = null;
        $this->albumMedia = [];
        $this->showCropModal = false;
        $this->imageToCrop = null;
    }

    public function loadAlbums()
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            $this->albums = [];
            return;
        }

        $this->albums = Album::where('wedding_id', $weddingId)
            ->withCount('media')
            ->get()
            ->map(function ($album) {
                return [
                    'id' => $album->id,
                    'name' => $album->name,
                    'cover_url' => $album->cover_url,
                    'media_count' => $album->media_count,
                ];
            })
            ->toArray();
    }

    public function selectAlbum($albumId)
    {
        \Log::info('selectAlbum called', ['album_id' => $albumId]);
        $this->selectedAlbumId = $albumId;
        $this->loadAlbumMedia($albumId);
        \Log::info('After loadAlbumMedia', [
            'selectedAlbumId' => $this->selectedAlbumId,
            'albumMedia_count' => count($this->albumMedia)
        ]);
    }

    public function backToAlbums()
    {
        $this->selectedAlbumId = null;
        $this->albumMedia = [];
    }

    public function loadAlbumMedia($albumId)
    {
        $album = Album::find($albumId);
        
        if (!$album) {
            $this->albumMedia = [];
            return;
        }

        $this->albumMedia = $album->media()
            ->where('mime_type', 'like', 'image/%')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getVariantUrl('thumbnail') ?? $media->getUrl(),
                    'filename' => $media->original_name,
                    'alt' => $media->original_name,
                    'width' => $media->width ?? null,
                    'height' => $media->height ?? null,
                ];
            })
            ->toArray();
            
        \Log::info('Loaded album media', [
            'album_id' => $albumId,
            'media_count' => count($this->albumMedia),
            'first_media' => $this->albumMedia[0] ?? null
        ]);
    }

    public function selectImage($mediaId)
    {
        $media = collect($this->albumMedia)->firstWhere('id', $mediaId);
        
        if (!$media) {
            return;
        }

        // Check if needs crop
        if ($this->needsCrop($media)) {
            $this->imageToCrop = $media;
            $this->initializeCropArea($media);
            $this->showCropModal = true;
        } else {
            // Select directly
            $this->dispatch('imageSelected', url: $media['url']);
            $this->close();
        }
    }

    protected function needsCrop($media): bool
    {
        if (!$this->maxWidth && !$this->maxHeight) {
            return false;
        }

        if (!isset($media['width']) || !isset($media['height'])) {
            return true; // Assume needs crop if dimensions unknown
        }

        $exceedsWidth = $this->maxWidth && $media['width'] > $this->maxWidth;
        $exceedsHeight = $this->maxHeight && $media['height'] > $this->maxHeight;

        return $exceedsWidth || $exceedsHeight;
    }

    protected function initializeCropArea($media)
    {
        $targetWidth = $this->maxWidth ?? $media['width'];
        $targetHeight = $this->maxHeight ?? $media['height'];

        $this->cropArea = [
            'x' => max(0, ($media['width'] - $targetWidth) / 2),
            'y' => max(0, ($media['height'] - $targetHeight) / 2),
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    public function confirmCrop()
    {
        if (!$this->imageToCrop) {
            return;
        }

        try {
            $media = SiteMedia::find($this->imageToCrop['id']);
            
            if (!$media) {
                session()->flash('error', 'Imagem não encontrada');
                return;
            }

            // Create crop request
            $request = request()->merge([
                'x' => round($this->cropArea['x']),
                'y' => round($this->cropArea['y']),
                'width' => round($this->cropArea['width']),
                'height' => round($this->cropArea['height']),
            ]);

            // Call the crop method
            $controller = app(\App\Http\Controllers\MediaController::class);
            $response = $controller->crop($request, (string)$media->id);

            $responseData = $response->getData(true);
            
            if (isset($responseData['data']['url'])) {
                $this->dispatch('imageSelected', url: $responseData['data']['url']);
                $this->close();
            } else {
                session()->flash('error', 'Erro ao processar imagem');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao processar imagem: ' . $e->getMessage());
            \Log::error('Crop error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function cancelCrop()
    {
        $this->showCropModal = false;
        $this->imageToCrop = null;
    }

    public function render()
    {
        return view('livewire.media-gallery-picker');
    }
}
