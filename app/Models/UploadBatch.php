<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * UploadBatch Model
 * 
 * Represents a batch of files being uploaded together.
 */
class UploadBatch extends Model
{
    use HasFactory, HasUuids;

    /**
     * Batch status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wedding_id',
        'album_id',
        'total_files',
        'completed_files',
        'failed_files',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_files' => 'integer',
            'completed_files' => 'integer',
            'failed_files' => 'integer',
        ];
    }

    /**
     * Get the wedding this batch belongs to.
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Get the album this batch is uploading to.
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get the media files in this batch.
     */
    public function media(): HasMany
    {
        return $this->hasMany(SiteMedia::class, 'batch_id');
    }

    /**
     * Check if the batch is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED 
            || ($this->completed_files + $this->failed_files) >= $this->total_files;
    }

    /**
     * Get the number of pending files.
     */
    public function getPendingFilesCount(): int
    {
        return max(0, $this->total_files - $this->completed_files - $this->failed_files);
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_files === 0) {
            return 100.0;
        }

        return (($this->completed_files + $this->failed_files) / $this->total_files) * 100;
    }
}
