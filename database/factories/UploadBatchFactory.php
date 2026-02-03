<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Album;
use App\Models\UploadBatch;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para criar instâncias de UploadBatch para testes.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UploadBatch>
 */
class UploadBatchFactory extends Factory
{
    /**
     * O nome do modelo correspondente à factory.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = UploadBatch::class;

    /**
     * Define o estado padrão do modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'album_id' => null,
            'total_files' => $this->faker->numberBetween(1, 10),
            'completed_files' => 0,
            'failed_files' => 0,
            'status' => UploadBatch::STATUS_PENDING,
        ];
    }

    /**
     * Estado para batch com álbum.
     */
    public function withAlbum(): static
    {
        return $this->state(fn (array $attributes) => [
            'album_id' => Album::factory(),
        ]);
    }

    /**
     * Estado para batch em processamento.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadBatch::STATUS_PROCESSING,
        ]);
    }

    /**
     * Estado para batch completo.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $totalFiles = $attributes['total_files'] ?? 1;
            return [
                'status' => UploadBatch::STATUS_COMPLETED,
                'completed_files' => $totalFiles,
                'failed_files' => 0,
            ];
        });
    }

    /**
     * Estado para batch falhado.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $totalFiles = $attributes['total_files'] ?? 1;
            return [
                'status' => UploadBatch::STATUS_FAILED,
                'completed_files' => 0,
                'failed_files' => $totalFiles,
            ];
        });
    }

    /**
     * Estado para batch cancelado.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadBatch::STATUS_CANCELLED,
        ]);
    }
}
