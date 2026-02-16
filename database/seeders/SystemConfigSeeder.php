<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'key' => 'site.max_file_size',
                'value' => json_encode(10485760), // 10MB in bytes
                'description' => 'Maximum file size for uploads in bytes (10MB)',
            ],
            [
                'key' => 'site.max_versions',
                'value' => json_encode(30),
                'description' => 'Maximum number of versions to keep per site',
            ],
            [
                'key' => 'site.max_storage_per_wedding',
                'value' => json_encode(524288000), // 500MB in bytes
                'description' => 'Maximum storage per wedding in bytes (500MB)',
            ],
            [
                'key' => 'planning.max_file_size',
                'value' => json_encode(26214400), // 25MB in bytes
                'description' => 'Maximum file size for planning attachments in bytes (25MB)',
            ],
            [
                'key' => 'planning.max_storage_per_wedding',
                'value' => json_encode(524288000), // 500MB in bytes
                'description' => 'Maximum storage per wedding for planning attachments in bytes (500MB)',
            ],
            [
                'key' => 'site.performance_threshold',
                'value' => json_encode(5242880), // 5MB in bytes
                'description' => 'Performance alert threshold in bytes (5MB)',
            ],
            [
                'key' => 'site.google_maps_api_key',
                'value' => json_encode(null),
                'description' => 'Google Maps API key for map integration',
            ],
            [
                'key' => 'site.mapbox_api_key',
                'value' => json_encode(null),
                'description' => 'Mapbox API key for map integration',
            ],
            [
                'key' => 'site.allowed_extensions',
                'value' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']),
                'description' => 'Allowed file extensions for uploads',
            ],
            [
                'key' => 'site.blocked_extensions',
                'value' => json_encode(['exe', 'bat', 'sh', 'php', 'js', 'html']),
                'description' => 'Blocked file extensions for security',
            ],
            [
                'key' => 'site.rate_limit_attempts',
                'value' => json_encode(5),
                'description' => 'Number of attempts before rate limiting',
            ],
            [
                'key' => 'site.rate_limit_minutes',
                'value' => json_encode(15),
                'description' => 'Minutes to block after rate limit exceeded',
            ],
            // Media management configurations
            [
                'key' => 'media.max_image_width',
                'value' => json_encode(4096),
                'description' => 'Maximum image width in pixels',
            ],
            [
                'key' => 'media.max_image_height',
                'value' => json_encode(4096),
                'description' => 'Maximum image height in pixels',
            ],
            [
                'key' => 'media.max_image_size',
                'value' => json_encode(10485760), // 10MB
                'description' => 'Maximum image file size in bytes (10MB)',
            ],
            [
                'key' => 'media.max_video_size',
                'value' => json_encode(104857600), // 100MB
                'description' => 'Maximum video file size in bytes (100MB)',
            ],
        ];

        foreach ($configs as $config) {
            DB::table('system_configs')->updateOrInsert(
                ['key' => $config['key']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
