<?php

namespace App\Services\Site;

use App\Models\Album;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SiteTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Clones template media assets into the target wedding and rewrites content URLs/IDs.
 */
class TemplateMediaCloneService
{
    /**
     * Copy referenced media (from other weddings) into the target site wedding.
     */
    public function replicateForSite(array $content, SiteLayout $targetSite, ?SiteTemplate $template = null): array
    {
        $paths = [];
        $this->collectStoragePaths($content, $paths);

        if ($paths === []) {
            return $content;
        }

        $processed = [];
        $replacements = [];
        $mediaIdMap = [];
        $albumIdMap = [];
        $allowedSourceWeddingIds = $this->resolveAllowedSourceWeddingIds($template);

        foreach (array_keys($paths) as $path) {
            $sourceMedia = $this->findMediaByPath($path);
            if (!$sourceMedia) {
                continue;
            }

            if ($sourceMedia->wedding_id === $targetSite->wedding_id) {
                continue;
            }

            if (
                $allowedSourceWeddingIds !== []
                && !in_array((string) $sourceMedia->wedding_id, $allowedSourceWeddingIds, true)
            ) {
                continue;
            }

            if (!isset($processed[$sourceMedia->id])) {
                $processed[$sourceMedia->id] = $this->cloneMedia($sourceMedia, $targetSite, $albumIdMap);
            }

            $clonePayload = $processed[$sourceMedia->id];
            $clonedMedia = $clonePayload['media'];
            $pathMap = $clonePayload['path_map'];

            $mediaIdMap[(string) $sourceMedia->id] = (string) $clonedMedia->id;
            $this->registerPathReplacements($sourceMedia->disk, $pathMap, $replacements);
        }

        if ($replacements === [] && $mediaIdMap === [] && $albumIdMap === []) {
            return $content;
        }

        return $this->rewriteContent($content, $replacements, $mediaIdMap, $albumIdMap);
    }

    /**
     * Recursively collect storage-relative paths from content values.
     *
     * @param array<string, string> $paths
     */
    private function collectStoragePaths(mixed $value, array &$paths): void
    {
        if (is_array($value)) {
            foreach ($value as $child) {
                $this->collectStoragePaths($child, $paths);
            }

            return;
        }

        if (!is_string($value) || trim($value) === '') {
            return;
        }

        if (preg_match_all('#/storage/([^"\'\s\)\(]+)#', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $path = trim((string) strtok($match, '?#'), '/');
                if ($path !== '') {
                    $paths[$path] = $path;
                }
            }
        }

        if (str_starts_with($value, 'sites/')) {
            $path = trim((string) strtok($value, '?#'), '/');
            if ($path !== '') {
                $paths[$path] = $path;
            }
        }
    }

    /**
     * Try to resolve a SiteMedia record by original/variant path.
     */
    private function findMediaByPath(string $path): ?SiteMedia
    {
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $media = SiteMedia::query()
            ->withoutGlobalScopes()
            ->where('path', $path)
            ->first();

        if ($media) {
            return $media;
        }

        $jsonNeedle = '"' . str_replace('"', '\"', $path) . '"';

        return SiteMedia::query()
            ->withoutGlobalScopes()
            ->whereRaw('variants::text like ?', ['%' . $jsonNeedle . '%'])
            ->first();
    }

    /**
     * Clone a source media file and its variants into target wedding.
     *
     * @param array<string, string> $albumIdMap
     * @return array{media: SiteMedia, path_map: array<string, string>}
     */
    private function cloneMedia(SiteMedia $source, SiteLayout $targetSite, array &$albumIdMap): array
    {
        $sourcePath = trim((string) $source->path, '/');
        if ($sourcePath === '') {
            return [
                'media' => $source,
                'path_map' => [],
            ];
        }

        $targetDisk = $source->disk ?: 'public';
        $sourceDirectory = 'sites/' . $targetSite->wedding_id . '/media';
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetFilename = Str::uuid()->toString() . ($extension !== '' ? '.' . $extension : '');
        $targetPath = $sourceDirectory . '/' . $targetFilename;

        if (!$this->copyFile($source->disk, $sourcePath, $targetPath)) {
            return [
                'media' => $source,
                'path_map' => [],
            ];
        }

        $targetVariants = [];
        $pathMap = [
            $sourcePath => $targetPath,
        ];

        foreach ((array) ($source->variants ?? []) as $variantName => $variantPath) {
            if (!is_string($variantPath) || trim($variantPath) === '') {
                continue;
            }

            $variantPath = trim($variantPath, '/');
            $variantExtension = pathinfo($variantPath, PATHINFO_EXTENSION);
            $variantTargetFilename = pathinfo($targetFilename, PATHINFO_FILENAME)
                . '_' . Str::slug((string) $variantName, '_')
                . ($variantExtension !== '' ? '.' . $variantExtension : '');
            $variantTargetPath = $sourceDirectory . '/' . $variantTargetFilename;

            if (!$this->copyFile($source->disk, $variantPath, $variantTargetPath)) {
                continue;
            }

            $targetVariants[(string) $variantName] = $variantTargetPath;
            $pathMap[$variantPath] = $variantTargetPath;
        }

        $targetAlbumId = $this->resolveTargetAlbumId($source, $targetSite, $albumIdMap);
        $targetSize = Storage::disk($targetDisk)->size($targetPath) ?: $source->size;

        $cloned = SiteMedia::query()
            ->withoutGlobalScopes()
            ->create([
                'site_layout_id' => $targetSite->id,
                'wedding_id' => $targetSite->wedding_id,
                'original_name' => $source->original_name,
                'path' => $targetPath,
                'disk' => $targetDisk,
                'size' => $targetSize,
                'mime_type' => $source->mime_type,
                'variants' => $targetVariants,
                'album_id' => $targetAlbumId,
                'status' => SiteMedia::STATUS_COMPLETED,
                'batch_id' => null,
                'error_message' => null,
                'width' => $source->width,
                'height' => $source->height,
            ]);

        $sourceAlbum = $source->album()->withoutGlobalScopes()->first();
        if ($sourceAlbum && $sourceAlbum->cover_media_id === $source->id && $targetAlbumId) {
            Album::query()
                ->withoutGlobalScopes()
                ->whereKey($targetAlbumId)
                ->update(['cover_media_id' => $cloned->id]);
        }

        return [
            'media' => $cloned,
            'path_map' => $pathMap,
        ];
    }

    /**
     * Resolve or create destination album for cloned media.
     *
     * @param array<string, string> $albumIdMap
     */
    private function resolveTargetAlbumId(SiteMedia $source, SiteLayout $targetSite, array &$albumIdMap): ?string
    {
        if (!$source->album_id) {
            return null;
        }

        $sourceAlbumId = (string) $source->album_id;

        if (isset($albumIdMap[$sourceAlbumId])) {
            return $albumIdMap[$sourceAlbumId];
        }

        $sourceAlbum = Album::query()
            ->withoutGlobalScopes()
            ->whereKey($sourceAlbumId)
            ->first();

        if (!$sourceAlbum) {
            return null;
        }

        $existingTargetAlbum = Album::query()
            ->withoutGlobalScopes()
            ->where('wedding_id', $targetSite->wedding_id)
            ->where('album_type_id', $sourceAlbum->album_type_id)
            ->where('name', $sourceAlbum->name)
            ->first();

        if ($existingTargetAlbum) {
            $albumIdMap[$sourceAlbumId] = (string) $existingTargetAlbum->id;

            return (string) $existingTargetAlbum->id;
        }

        $createdTargetAlbum = Album::query()
            ->withoutGlobalScopes()
            ->create([
                'wedding_id' => $targetSite->wedding_id,
                'album_type_id' => $sourceAlbum->album_type_id,
                'name' => $sourceAlbum->name,
                'description' => $sourceAlbum->description,
                'cover_media_id' => null,
            ]);

        $albumIdMap[$sourceAlbumId] = (string) $createdTargetAlbum->id;

        return (string) $createdTargetAlbum->id;
    }

    /**
     * Copy file between paths on the same disk, with fallback via get/put.
     */
    private function copyFile(string $disk, string $from, string $to): bool
    {
        $storage = Storage::disk($disk);

        if (!$storage->exists($from)) {
            return false;
        }

        if ($from === $to) {
            return true;
        }

        try {
            if ($storage->copy($from, $to)) {
                return true;
            }
        } catch (\Throwable) {
            // fallback below
        }

        try {
            $contents = $storage->get($from);
            $storage->put($to, $contents);

            return $storage->exists($to);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Register replacement tokens from source path map.
     *
     * @param array<string, string> $pathMap
     * @param array<string, string> $replacements
     */
    private function registerPathReplacements(string $disk, array $pathMap, array &$replacements): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        foreach ($pathMap as $sourcePath => $targetPath) {
            $sourceStorageUrl = '/storage/' . ltrim($sourcePath, '/');
            $targetStorageUrl = '/storage/' . ltrim($targetPath, '/');

            $replacements[$sourcePath] = $targetPath;
            $replacements[$sourceStorageUrl] = $targetStorageUrl;

            if ($appUrl !== '') {
                $replacements[$appUrl . $sourceStorageUrl] = $appUrl . $targetStorageUrl;
            }

            $diskSourceUrl = Storage::disk($disk)->url($sourcePath);
            $diskTargetUrl = Storage::disk($disk)->url($targetPath);
            $replacements[$diskSourceUrl] = $diskTargetUrl;
        }
    }

    /**
     * Rewrite URLs and media/album IDs in the content payload.
     *
     * @param array<string, string> $replacements
     * @param array<string, string> $mediaIdMap
     * @param array<string, string> $albumIdMap
     */
    private function rewriteContent(mixed $value, array $replacements, array $mediaIdMap, array $albumIdMap): mixed
    {
        if (is_array($value)) {
            $rewritten = [];

            foreach ($value as $key => $child) {
                if (is_array($child)) {
                    $rewritten[$key] = $this->rewriteContent($child, $replacements, $mediaIdMap, $albumIdMap);
                    continue;
                }

                if (!is_string($child)) {
                    $rewritten[$key] = $child;
                    continue;
                }

                $nextValue = $this->rewriteString($child, $replacements);

                if (($key === 'mediaId' || $key === 'media_id') && isset($mediaIdMap[$nextValue])) {
                    $nextValue = $mediaIdMap[$nextValue];
                }

                if (($key === 'albumId' || $key === 'album_id') && isset($albumIdMap[$nextValue])) {
                    $nextValue = $albumIdMap[$nextValue];
                }

                $rewritten[$key] = $nextValue;
            }

            if (
                isset($rewritten['id'])
                && is_string($rewritten['id'])
                && isset($mediaIdMap[$rewritten['id']])
                && $this->looksLikeMediaItem($rewritten)
            ) {
                $rewritten['id'] = $mediaIdMap[$rewritten['id']];
            }

            return $rewritten;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->rewriteString($value, $replacements);
    }

    /**
     * Apply all replacement tokens to a string.
     *
     * @param array<string, string> $replacements
     */
    private function rewriteString(string $value, array $replacements): string
    {
        if ($replacements === []) {
            return $value;
        }

        uksort($replacements, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $result = $value;
        foreach ($replacements as $from => $to) {
            if ($from === '' || $from === $to) {
                continue;
            }

            if ($result === $from) {
                return $to;
            }

            if (str_contains($result, $from)) {
                $result = str_replace($from, $to, $result);
            }
        }

        return $result;
    }

    /**
     * Heuristic to identify gallery/media item objects.
     *
     * @param array<string, mixed> $item
     */
    private function looksLikeMediaItem(array $item): bool
    {
        return isset($item['url'])
            || isset($item['originalUrl'])
            || isset($item['original_url'])
            || isset($item['displayUrl'])
            || isset($item['display_url'])
            || isset($item['thumbnailUrl'])
            || isset($item['thumbnail_url']);
    }

    /**
     * Resolve eligible source weddings for media cloning.
     *
     * @return array<string>
     */
    private function resolveAllowedSourceWeddingIds(?SiteTemplate $template): array
    {
        if (!$template) {
            return [];
        }

        $allowed = [];

        if (is_string($template->wedding_id) && $template->wedding_id !== '') {
            $allowed[] = $template->wedding_id;
        }

        $editorWeddingId = $template->editorSite()
            ->withoutGlobalScopes()
            ->value('wedding_id');

        if (is_string($editorWeddingId) && $editorWeddingId !== '') {
            $allowed[] = $editorWeddingId;
        }

        return array_values(array_unique($allowed));
    }
}
