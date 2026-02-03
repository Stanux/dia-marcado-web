# Correção: Erro 404 Not Found no Upload

## Problema

Ao tentar fazer upload de imagens na Galeria de Mídias, o erro **404 Not Found** era retornado.

### Causa

Os composables Vue estavam chamando rotas sem o prefixo `/admin`, mas as rotas estão definidas dentro do grupo `prefix('admin')` no arquivo `routes/web.php`.

**Rotas chamadas (incorretas):**
- ❌ `/media/upload` 
- ❌ `/albums`
- ❌ `/media/{id}`

**Rotas corretas (com prefixo):**
- ✅ `/admin/media/upload`
- ✅ `/admin/albums`
- ✅ `/admin/media/{id}`

## Solução Implementada

### 1. Corrigido `useMediaUpload.ts`

**Arquivo**: `resources/js/Composables/useMediaUpload.ts`

```typescript
// ANTES
router.post('/media/upload', formData, { ... })

// DEPOIS
router.post('/admin/media/upload', formData, { ... })
```

### 2. Corrigido `useAlbums.ts`

**Arquivo**: `resources/js/Composables/useAlbums.ts`

```typescript
// ANTES
router.post('/albums', { name }, { ... })

// DEPOIS
router.post('/admin/albums', { name }, { ... })
```

### 3. Corrigido `useMediaGallery.ts`

**Arquivo**: `resources/js/Composables/useMediaGallery.ts`

```typescript
// ANTES
router.delete(`/media/${mediaId}`, { ... })

// DEPOIS
router.delete(`/admin/media/${mediaId}`, { ... })
```

## Arquivos Modificados

| Arquivo | Mudança |
|---------|---------|
| `resources/js/Composables/useMediaUpload.ts` | Rota de upload: `/media/upload` → `/admin/media/upload` |
| `resources/js/Composables/useAlbums.ts` | Rota de criação: `/albums` → `/admin/albums` |
| `resources/js/Composables/useMediaGallery.ts` | Rota de delete: `/media/{id}` → `/admin/media/{id}` |

## Build Realizado

```bash
npm run build
```

✅ Build concluído com sucesso  
✅ Novos bundles gerados

## Rotas Definidas no Backend

**Arquivo**: `routes/web.php`

```php
Route::middleware(['auth', 'wedding.inertia'])->prefix('admin')->group(function () {
    // Media Screen routes
    Route::get('/midias', [MediaScreenController::class, 'index'])
        ->name('midias.index');
    
    Route::post('/albums', [AlbumController::class, 'store'])
        ->name('albums.store');
    
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->name('media.upload');
    
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])
        ->name('media.destroy');
    
    // ... outras rotas
});
```

## Verificação

Após aplicar as mudanças:

### 1. Limpar cache do navegador
- Pressione `Ctrl+Shift+R` (ou `Cmd+Shift+R` no Mac)
- Ou abra o DevTools (F12) → Network → Marque "Disable cache"

### 2. Testar upload
1. Acesse a **Galeria de Mídias**
2. Selecione um álbum
3. Arraste ou selecione uma imagem
4. ✅ **Upload deve funcionar sem erro 404!**

### 3. Verificar no Network (DevTools)
- Abra F12 → Network
- Faça um upload
- Verifique que a requisição vai para: `POST /admin/media/upload`
- Status deve ser: **200 OK** (não 404)

## Rotas Disponíveis

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/admin/midias` | Página da galeria de mídias |
| POST | `/admin/albums` | Criar novo álbum |
| POST | `/admin/media/upload` | Upload de arquivo |
| DELETE | `/admin/media/{id}` | Deletar mídia |

## Notas

- ✅ Todas as rotas agora usam o prefixo `/admin` consistentemente
- ✅ Middleware `auth` e `wedding.inertia` aplicados
- ✅ Rotas protegidas por autenticação
- ✅ Contexto de wedding garantido pelo middleware

## Próximos Passos

Se ainda houver problemas:

1. **Verificar logs do Laravel**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verificar logs do Nginx**:
   ```bash
   docker-compose logs nginx
   ```

3. **Verificar console do navegador**:
   - F12 → Console
   - Procurar por erros JavaScript

4. **Verificar Network**:
   - F12 → Network
   - Verificar requisições e respostas
