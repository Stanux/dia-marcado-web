# Media Screen Type Definitions

This directory contains TypeScript type definitions for the Media Screen feature.

## Overview

The `media-screen.ts` file provides comprehensive type definitions for:
- Data models (Album, Media, etc.)
- Component props and events
- Composable return types
- Internal state types
- Error and notification types

## Usage in Vue Components

### Using Props Types

```vue
<script setup lang="ts">
import type { AlbumListProps } from '@/types/media-screen';

// Define props with TypeScript
const props = defineProps<AlbumListProps>();

// TypeScript will enforce correct prop types
// props.albums is Album[]
// props.selectedAlbumId is number | undefined
</script>
```

### Using Events Types

```vue
<script setup lang="ts">
import type { AlbumListEvents } from '@/types/media-screen';

// Define emits with TypeScript
const emit = defineEmits<AlbumListEvents>();

// TypeScript will enforce correct event signatures
function handleAlbumClick(albumId: number) {
  emit('album-selected', albumId); // ✓ Correct
  // emit('album-selected', 'wrong'); // ✗ Type error
}
</script>
```

### Using Data Models

```typescript
import type { Album, Media } from '@/types/media-screen';

// Create typed variables
const album: Album = {
  id: 1,
  name: 'Wedding Photos',
  media_count: 10,
  media: [],
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
};

const media: Media = {
  id: 1,
  album_id: 1,
  filename: 'photo.jpg',
  type: 'image',
  mime_type: 'image/jpeg',
  size: 1024000,
  url: 'https://example.com/photo.jpg',
  thumbnail_url: 'https://example.com/thumb.jpg',
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
};
```

### Using Composable Types

```typescript
import type { UseAlbumsReturn } from '@/types/media-screen';
import { ref, type Ref } from 'vue';

function useAlbums(initialAlbums: Album[]): UseAlbumsReturn {
  const albums = ref<Album[]>(initialAlbums);
  const selectedAlbum = ref<Album | null>(null);
  const isLoading = ref(false);

  // Implementation...

  return {
    albums,
    selectedAlbum,
    isLoading,
    selectAlbum,
    createAlbum,
    refreshAlbums,
  };
}
```

## Type Safety Benefits

1. **Compile-time Errors**: Catch type mismatches before runtime
2. **IntelliSense**: Get autocomplete and inline documentation in your IDE
3. **Refactoring Safety**: Rename properties with confidence
4. **Documentation**: Types serve as inline documentation
5. **Consistency**: Ensure data structures match across components

## Integration with Inertia.js

When receiving data from Laravel via Inertia.js:

```vue
<script setup lang="ts">
import type { MediaScreenProps } from '@/types/media-screen';

// Inertia will pass props matching this type
const props = defineProps<MediaScreenProps>();

// TypeScript ensures the data from Laravel matches expected structure
</script>
```

## Validation Types

The validation types help ensure type safety during file uploads:

```typescript
import type { ValidationResult, UploadError } from '@/types/media-screen';

function validateFiles(files: File[]): ValidationResult {
  const validFiles: File[] = [];
  const invalidFiles: File[] = [];
  const errors: string[] = [];

  // Validation logic...

  return {
    isValid: invalidFiles.length === 0,
    validFiles,
    invalidFiles,
    errors,
  };
}

function handleUploadError(error: UploadError): void {
  console.error(error.message);
  console.error('Failed files:', error.files);
  if (error.code) {
    console.error('Error code:', error.code);
  }
}
```

## Best Practices

1. **Always import types with `type` keyword**: `import type { Album } from '@/types/media-screen'`
2. **Use strict mode**: Enabled in `tsconfig.json` for maximum safety
3. **Avoid `any` type**: Use proper types or `unknown` if type is truly unknown
4. **Export all types**: Make types available for testing and other modules
5. **Document complex types**: Add JSDoc comments for clarity

## Adding New Types

When adding new types to the Media Screen feature:

1. Add the type definition to `media-screen.ts`
2. Export the type
3. Add JSDoc comments explaining the type's purpose
4. Update this README with usage examples
5. Add tests in `tests/unit/types.test.ts`

## Related Files

- Type definitions: `resources/js/types/media-screen.ts`
- Type tests: `tests/unit/types.test.ts`
- TypeScript config: `tsconfig.json`
- Vitest config: `vitest.config.js`
