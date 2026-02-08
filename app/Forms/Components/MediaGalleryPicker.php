<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Custom Filament field for selecting images from the media gallery with crop support.
 * 
 * This component integrates with the Vue MediaGalleryModal to allow selecting
 * images from albums and automatically cropping them if they exceed specified dimensions.
 */
class MediaGalleryPicker extends Field
{
    protected string $view = 'forms.components.media-gallery-picker';

    protected ?int $imageMaxWidth = null;
    protected ?int $imageMaxHeight = null;
    protected string $buttonLabel = 'Selecionar da Galeria';

    /**
     * Set maximum width for the image
     */
    public function imageMaxWidth(?int $width): static
    {
        $this->imageMaxWidth = $width;
        return $this;
    }

    /**
     * Set maximum height for the image
     */
    public function imageMaxHeight(?int $height): static
    {
        $this->imageMaxHeight = $height;
        return $this;
    }

    /**
     * Set button label
     */
    public function buttonLabel(string $label): static
    {
        $this->buttonLabel = $label;
        return $this;
    }

    /**
     * Get maximum width
     */
    public function getImageMaxWidth(): ?int
    {
        return $this->imageMaxWidth;
    }

    /**
     * Get maximum height
     */
    public function getImageMaxHeight(): ?int
    {
        return $this->imageMaxHeight;
    }

    /**
     * Get button label
     */
    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }
}
