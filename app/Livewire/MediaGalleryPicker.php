<?php

namespace App\Livewire;

use App\Models\Album;
use App\Models\SiteMedia;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

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
    public array $cropArea = ['x' => 0, 'y' => 0, 'width' => null, 'height' => null];

    public ?string $statePath = null;

    public function getListeners()
    {
        return [
            "openMediaGallery:{$this->statePath}" => 'open',
        ];
    }

    public function mount(?string $statePath = null, ?int $maxWidth = null, ?int $maxHeight = null)
    {
        $this->statePath = $statePath;
        if ($maxWidth !== null) {
            $this->maxWidth = $maxWidth;
        }
        if ($maxHeight !== null) {
            $this->maxHeight = $maxHeight;
        }
        $this->loadAlbums();
    }

    public function open($payload = null, $maxHeight = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $data = (array) $payload;
            if (array_key_exists('maxWidth', $data) || array_key_exists('max_width', $data)) {
                $this->maxWidth = $data['maxWidth'] ?? $data['max_width'];
            }
            if (array_key_exists('maxHeight', $data) || array_key_exists('max_height', $data)) {
                $this->maxHeight = $data['maxHeight'] ?? $data['max_height'];
            }
        } else {
            if ($payload !== null) {
                $this->maxWidth = $payload;
            }
            if ($maxHeight !== null) {
                $this->maxHeight = $maxHeight;
            }
        }
        $this->show = true;
        // Don't reset statePath here
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

    public function selectImage($mediaId, $maxWidth = null, $maxHeight = null)
    {
        if ($this->maxWidth === null && $maxWidth !== null) {
            $this->maxWidth = (int) $maxWidth;
        }
        if ($this->maxHeight === null && $maxHeight !== null) {
            $this->maxHeight = (int) $maxHeight;
        }
        $media = collect($this->albumMedia)->firstWhere('id', $mediaId);
        
        if (!$media) {
            return;
        }

        // Check if needs crop
        if ($this->needsCrop($media)) {
            $this->imageToCrop = $media;
            $this->initializeCropArea($media);
            $this->showCropModal = true;
            $this->show = false; // Hide gallery while cropping
        } else {
            // Select directly
            $this->dispatch('image-selected', id: $this->statePath, url: $media['url']);
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
        
        \Log::info('Initialized crop area', [
            'media_dimensions' => ['width' => $media['width'], 'height' => $media['height']],
            'target_dimensions' => ['width' => $targetWidth, 'height' => $targetHeight],
            'crop_area' => $this->cropArea,
        ]);
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

            \Log::info('Starting crop', [
                'media_id' => $media->id,
                'crop_area' => $this->cropArea,
                'user_id' => auth()->id(),
                'wedding_id' => auth()->user()?->current_wedding_id,
            ]);

            // Validate crop area
            $cropData = [
                'x' => (int) round($this->cropArea['x']),
                'y' => (int) round($this->cropArea['y']),
                'width' => (int) round($this->cropArea['width']),
                'height' => (int) round($this->cropArea['height']),
            ];

            // Call the crop method directly with proper request
            $controller = app(\App\Http\Controllers\MediaController::class);
            
            // Create a proper request with the crop data
            $cropRequest = Request::create(
                route('media.crop', ['id' => $media->id]),
                'POST',
                $cropData
            );
            
            // Set the user on the request
            $cropRequest->setUserResolver(function () {
                return auth()->user();
            });

            $response = $controller->crop($cropRequest, (string)$media->id);
            $responseData = $response->getData(true);
            
            \Log::info('Crop response', [
                'success' => $responseData['success'] ?? false,
                'message' => $responseData['message'] ?? 'No message',
                'has_url' => isset($responseData['data']['url']),
            ]);
            
            if (isset($responseData['data']['url'])) {
                $this->dispatch('image-selected', id: $this->statePath, url: $responseData['data']['url']);
                $this->close();
                
                session()->flash('success', 'Imagem cortada com sucesso!');
            } else {
                $errorMessage = $responseData['message'] ?? 'Erro ao processar imagem';
                session()->flash('error', $errorMessage);
                $this->cancelCrop();
            }
        } catch (\Throwable $e) {
            \Log::error('Crop error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'media_id' => $this->imageToCrop['id'] ?? null,
            ]);
            
            session()->flash('error', 'Erro ao processar imagem: ' . $e->getMessage());
            $this->cancelCrop();
        }
    }

    public function cancelCrop()
    {
        $this->showCropModal = false;
        $this->imageToCrop = null;
        $this->show = true; // Re-open gallery
    }

    public function render()
    {
        return view('livewire.media-gallery-picker');
    }
}
