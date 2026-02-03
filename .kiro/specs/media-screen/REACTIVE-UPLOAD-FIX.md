# Correção: Upload Reativo com Spinner de Progresso

## Problemas Identificados

### 1. Falta de Spinner de Loading Durante Upload
- **Problema**: Ao fazer upload de uma imagem, ela não aparecia com indicador de progresso
- **Causa**: O controller estava usando `back()->with()` que causava reload completo da página, impedindo que o usuário visse o spinner

### 2. Comportamento Não Reativo (Tela Recarregando)
- **Problema**: Ao confirmar exclusão ou fazer upload, a tela recarregava criando uma "cópia deslocada"
- **Causa**: O uso de `RedirectResponse` com Inertia causava reload completo da página em vez de atualização reativa

## Solução Implementada

Mudamos de **Inertia.js (router.post/delete)** para **Axios (HTTP direto)** para permitir atualizações reativas sem reload de página.

### Arquivos Modificados

#### 1. `app/Http/Controllers/MediaController.php`

**Mudanças:**
- ✅ Método `upload()`: Retorna `JsonResponse` em vez de `RedirectResponse`
- ✅ Método `destroy()`: Retorna `JsonResponse` em vez de `RedirectResponse`
- ✅ Removido import de `RedirectResponse`

**ANTES:**
```php
public function upload(Request $request): RedirectResponse
{
    // ...
    return back()->with('media', [...]);
}

public function destroy(Request $request, string $id): RedirectResponse
{
    // ...
    return back()->with('message', 'Mídia excluída com sucesso.');
}
```

**DEPOIS:**
```php
public function upload(Request $request): \Illuminate\Http\JsonResponse
{
    // ...
    return response()->json([
        'success' => true,
        'media' => [...]
    ], 201);
}

public function destroy(Request $request, string $id): \Illuminate\Http\JsonResponse
{
    // ...
    return response()->json([
        'success' => true,
        'message' => 'Mídia excluída com sucesso.'
    ], 200);
}
```

#### 2. `resources/js/Composables/useMediaUpload.ts`

**Mudanças:**
- ✅ Substituído `router.post` (Inertia) por `axios.post`
- ✅ Adicionado tracking de progresso via `onUploadProgress`
- ✅ Tratamento de resposta JSON em vez de props do Inertia

**ANTES:**
```typescript
import { router } from '@inertiajs/vue3';

const media = await new Promise<Media>((resolve, reject) => {
  router.post('/admin/media/upload', formData, {
    preserveScroll: true,
    forceFormData: true,
    onProgress: (progress) => {
      uploadingFile.progress = Math.round(progress.percentage);
    },
    onSuccess: (page) => {
      const createdMedia = (page.props as any).media as Media;
      resolve(createdMedia);
    }
  });
});
```

**DEPOIS:**
```typescript
import axios from 'axios';

const response = await axios.post('/admin/media/upload', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
  },
  onUploadProgress: (progressEvent) => {
    if (progressEvent.total) {
      const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
      uploadingFile.progress = percentCompleted;
    }
  },
});

if (response.data.success && response.data.media) {
  uploadingFile.status = 'completed';
  return response.data.media as Media;
}
```

#### 3. `resources/js/Composables/useMediaGallery.ts`

**Mudanças:**
- ✅ Substituído `router.delete` (Inertia) por `axios.delete`
- ✅ Substituído `router.get` (Inertia) por `axios.get`
- ✅ Tratamento de resposta JSON

**ANTES:**
```typescript
import { router } from '@inertiajs/vue3';

const deleteMedia = async (mediaId: number): Promise<void> => {
  return new Promise((resolve, reject) => {
    router.delete(`/admin/media/${mediaId}`, {
      preserveScroll: true,
      onSuccess: () => {
        media.value = media.value.filter(m => m.id !== mediaId);
        resolve();
      }
    });
  });
};
```

**DEPOIS:**
```typescript
import axios from 'axios';

const deleteMedia = async (mediaId: number): Promise<void> => {
  try {
    const response = await axios.delete(`/admin/media/${mediaId}`);
    
    if (response.data.success) {
      media.value = media.value.filter(m => m.id !== mediaId);
    }
  } catch (error: any) {
    throw new Error(error.response?.data?.message || 'Failed to delete media');
  }
};
```

## Como Funciona Agora

### Fluxo de Upload (Reativo)

1. **Usuário seleciona arquivo** → `UploadArea.vue` detecta
2. **Arquivo adicionado à lista de upload** → `uploadingFiles.value.push(uploadingFile)`
3. **Spinner aparece imediatamente** → Componente renderiza item com status "uploading"
4. **Progresso atualizado em tempo real** → `onUploadProgress` atualiza `uploadingFile.progress`
5. **Upload completa** → Status muda para "completed", imagem aparece na galeria
6. **Spinner removido após 2s** → Delay para mostrar estado de sucesso

### Fluxo de Exclusão (Reativo)

1. **Usuário confirma exclusão** → `MediaItem.vue` emite evento
2. **Requisição DELETE via axios** → Sem reload de página
3. **Resposta JSON recebida** → `{ success: true, message: '...' }`
4. **Item removido da lista reativamente** → `media.value.filter(...)`
5. **Galeria atualiza automaticamente** → Vue detecta mudança e re-renderiza

## Benefícios da Solução

### ✅ Upload com Progresso Visível
- Spinner aparece imediatamente ao selecionar arquivo
- Percentual de progresso atualizado em tempo real (0-100%)
- Estado visual claro: uploading → completed → removido

### ✅ Comportamento Totalmente Reativo
- **Sem reload de página** - Atualizações instantâneas
- **Sem "cópia deslocada"** - Interface permanece estável
- **Transições suaves** - Animações CSS funcionam corretamente

### ✅ Melhor UX
- Feedback imediato ao usuário
- Múltiplos uploads simultâneos com progresso individual
- Mensagens de erro claras e específicas

### ✅ Performance
- Menos requisições ao servidor (sem reload)
- Menos dados transferidos (JSON vs HTML completo)
- Mais rápido e responsivo

## Estrutura de Resposta JSON

### Upload Bem-Sucedido
```json
{
  "success": true,
  "media": {
    "id": 123,
    "album_id": "uuid-here",
    "filename": "image.jpg",
    "type": "image",
    "mime_type": "image/jpeg",
    "size": 1024000,
    "url": "/storage/sites/wedding-id/uuid.jpg",
    "thumbnail_url": "/storage/sites/wedding-id/uuid_thumb.jpg",
    "created_at": "2026-02-02T17:00:00.000000Z",
    "updated_at": "2026-02-02T17:00:00.000000Z"
  }
}
```

### Upload com Erro
```json
{
  "success": false,
  "message": "Arquivo muito grande. O tamanho máximo permitido é 100MB."
}
```

### Exclusão Bem-Sucedida
```json
{
  "success": true,
  "message": "Mídia excluída com sucesso."
}
```

### Exclusão com Erro
```json
{
  "success": false,
  "message": "Mídia não encontrada."
}
```

## Componentes Envolvidos

### Upload Flow
1. `UploadArea.vue` - Área de drag-and-drop, mostra lista de uploads
2. `useMediaUpload.ts` - Lógica de upload com axios e tracking de progresso
3. `MediaController@upload` - Backend que processa arquivo e retorna JSON

### Delete Flow
1. `MediaItem.vue` - Item individual com botão de exclusão
2. `MediaGallery.vue` - Grid de itens, propaga evento de exclusão
3. `useMediaGallery.ts` - Lógica de exclusão com axios
4. `MediaController@destroy` - Backend que remove arquivo e retorna JSON

## Verificação

### Testar Upload
1. Acesse a Galeria de Mídias
2. Selecione uma imagem para upload
3. ✅ Deve aparecer imediatamente na lista "Enviando arquivos"
4. ✅ Deve mostrar spinner animado
5. ✅ Deve mostrar percentual de progresso (0% → 100%)
6. ✅ Ao completar, deve mudar para ícone de check verde
7. ✅ Após 2 segundos, deve desaparecer da lista de upload
8. ✅ Imagem deve aparecer na galeria sem reload de página

### Testar Exclusão
1. Clique no botão de excluir em uma imagem
2. Confirme a exclusão
3. ✅ Imagem deve desaparecer imediatamente da galeria
4. ✅ Não deve haver reload de página
5. ✅ Não deve aparecer "cópia deslocada"
6. ✅ Notificação de sucesso deve aparecer

## Notas Técnicas

### Axios vs Inertia
- **Axios**: Requisições HTTP diretas, retorna JSON, permite controle total
- **Inertia**: Framework para SPAs, gerencia estado da página, causa reloads

### Por que mudamos?
- Inertia é ótimo para navegação entre páginas
- Para operações CRUD dentro de uma página, axios é mais apropriado
- Permite atualizações reativas sem interferir no estado da página

### CSRF Protection
- Axios automaticamente inclui o token CSRF do Laravel
- Configurado no `bootstrap.js` via `axios.defaults.headers.common['X-CSRF-TOKEN']`

## Status

**✅ PROBLEMA TOTALMENTE RESOLVIDO**

- ✅ Spinner de loading aparece durante upload
- ✅ Progresso atualizado em tempo real
- ✅ Comportamento totalmente reativo
- ✅ Sem reload de página
- ✅ Sem "cópia deslocada"
- ✅ UX melhorada significativamente


---

## ATUALIZAÇÃO: Correção de Duplicação de Imagens (RESOLVIDO ✅)

### Problema Adicional Encontrado

Após implementar o upload reativo, foi identificado que:
- **Upload**: Ao postar uma imagem, ela aparecia **duplicada** (duas cópias)
- **Exclusão**: Ao excluir uma imagem, **ambas as cópias** eram removidas

### Causa Raiz

No componente `MediaGalleryWrapper.vue`, estávamos adicionando/removendo mídia em **dois lugares**:

```typescript
// ❌ ERRADO - Adiciona duas vezes no mesmo array
selectedAlbum.value.media.push(...uploadedMedia);  // 1ª vez
albums.value[albumIndex].media.push(...uploadedMedia);  // 2ª vez (mesmo array!)
```

O problema é que `selectedAlbum.value` é uma **referência direta** ao objeto dentro do array `albums.value`. Quando fazemos `selectAlbum()`, o código faz:

```typescript
const album = albums.value.find(a => a.id === albumId);
selectedAlbum.value = album || null;  // Referência, não cópia!
```

Isso significa que `selectedAlbum.value` e `albums.value[index]` **apontam para o mesmo objeto**. Então ao fazer `push` em ambos, estávamos adicionando duas vezes no mesmo array.

### Solução Aplicada

Removemos a duplicação - agora atualizamos apenas `selectedAlbum.value`:

```typescript
// ✅ CORRETO - Adiciona apenas uma vez
selectedAlbum.value.media.push(...uploadedMedia);
selectedAlbum.value.media_count += uploadedMedia.length;

// Não precisa atualizar albums.value[index] porque é a mesma referência!
```

### Arquivos Modificados

**`resources/js/Components/MediaScreen/MediaGalleryWrapper.vue`**

#### Upload (handleMediaUploaded)

**ANTES:**
```typescript
const handleMediaUploaded = (uploadedMedia: Media[]): void => {
  selectedAlbum.value.media.push(...uploadedMedia);
  selectedAlbum.value.media_count += uploadedMedia.length;
  
  // ❌ Duplicação - atualiza o mesmo array duas vezes
  const albumIndex = albums.value.findIndex(a => a.id === selectedAlbum.value!.id);
  if (albumIndex !== -1) {
    albums.value[albumIndex].media.push(...uploadedMedia);
    albums.value[albumIndex].media_count += uploadedMedia.length;
  }
};
```

**DEPOIS:**
```typescript
const handleMediaUploaded = (uploadedMedia: Media[]): void => {
  // ✅ Atualiza apenas uma vez
  selectedAlbum.value.media.push(...uploadedMedia);
  selectedAlbum.value.media_count += uploadedMedia.length;
  
  // Não precisa atualizar albums.value porque selectedAlbum é uma referência
};
```

#### Exclusão (handleMediaDeleted)

**ANTES:**
```typescript
const handleMediaDeleted = async (mediaId: number): Promise<void> => {
  await deleteMedia(mediaId);
  
  // Remove do selectedAlbum
  const mediaIndex = selectedAlbum.value.media.findIndex(m => m.id === mediaId);
  if (mediaIndex !== -1) {
    selectedAlbum.value.media.splice(mediaIndex, 1);
    selectedAlbum.value.media_count -= 1;
  }
  
  // ❌ Duplicação - remove do mesmo array duas vezes
  const albumIndex = albums.value.findIndex(a => a.id === selectedAlbum.value!.id);
  if (albumIndex !== -1) {
    const albumMediaIndex = albums.value[albumIndex].media.findIndex(m => m.id === mediaId);
    if (albumMediaIndex !== -1) {
      albums.value[albumIndex].media.splice(albumMediaIndex, 1);
      albums.value[albumIndex].media_count -= 1;
    }
  }
};
```

**DEPOIS:**
```typescript
const handleMediaDeleted = async (mediaId: number): Promise<void> => {
  await deleteMedia(mediaId);
  
  // ✅ Remove apenas uma vez
  const mediaIndex = selectedAlbum.value.media.findIndex(m => m.id === mediaId);
  if (mediaIndex !== -1) {
    selectedAlbum.value.media.splice(mediaIndex, 1);
    selectedAlbum.value.media_count -= 1;
  }
  
  // Não precisa atualizar albums.value porque selectedAlbum é uma referência
};
```

### Conceito: Referência vs Cópia

Em JavaScript/TypeScript, objetos são passados por **referência**:

```typescript
const albums = [{ id: 1, media: [] }];
const selected = albums[0];  // Referência, não cópia

selected.media.push('foto1');  // Adiciona no array original
console.log(albums[0].media);  // ['foto1'] - mesmo array!

selected.media.push('foto2');  // Adiciona novamente
albums[0].media.push('foto3'); // Adiciona no "mesmo" array
console.log(selected.media);   // ['foto1', 'foto2', 'foto3'] - todos no mesmo lugar
```

### Resultado

✅ **Upload**: Imagem aparece apenas **uma vez** na galeria  
✅ **Exclusão**: Remove apenas **uma imagem** (a correta)  
✅ **Sincronização**: `albums` e `selectedAlbum` sempre em sincronia (são o mesmo objeto)  
✅ **Performance**: Menos operações desnecessárias  

### Verificação

1. **Testar Upload**:
   - Selecione uma imagem
   - ✅ Deve aparecer apenas **uma vez** na galeria
   - ✅ Contador de mídias deve aumentar em 1

2. **Testar Exclusão**:
   - Clique para excluir uma imagem
   - ✅ Deve remover apenas **aquela imagem**
   - ✅ Outras imagens devem permanecer intactas
   - ✅ Contador de mídias deve diminuir em 1

**Status: PROBLEMA TOTALMENTE RESOLVIDO** 🎉
