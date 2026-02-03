# Correção: Erro "Inertia requests must receive a valid Inertia response"

## Problema

Ao fazer upload de mídia ou criar álbum, o erro aparecia:

```
All Inertia requests must receive a valid Inertia response, 
however a plain JSON response was received.
```

### Causa

Os controllers `MediaController` e `AlbumController` estavam retornando `JsonResponse` (JSON puro), mas quando usamos **Inertia.js** no frontend, todas as requisições devem retornar respostas Inertia compatíveis.

**Problema:**
- Frontend: Usa `router.post()` do Inertia.js
- Backend: Retorna `response()->json()` (JSON puro)
- Resultado: Incompatibilidade → Erro

## Solução Implementada

### 1. MediaController - Método `upload()`

**Arquivo**: `app/Http/Controllers/MediaController.php`

**ANTES:**
```php
public function upload(Request $request): JsonResponse
{
    // ...
    return response()->json([
        'id' => $media->id,
        // ...
    ], 201);
}
```

**DEPOIS:**
```php
use Illuminate\Http\RedirectResponse;

public function upload(Request $request): RedirectResponse
{
    // ...
    return back()->with('media', [
        'id' => $media->id,
        // ...
    ]);
}
```

### 2. MediaController - Método `destroy()`

**ANTES:**
```php
public function destroy(Request $request, string $id): JsonResponse
{
    // ...
    return response()->json([
        'message' => 'Mídia excluída com sucesso.',
    ], 200);
}
```

**DEPOIS:**
```php
public function destroy(Request $request, string $id): RedirectResponse
{
    // ...
    return back()->with('message', 'Mídia excluída com sucesso.');
}
```

### 3. AlbumController - Método `store()`

**Arquivo**: `app/Http/Controllers/AlbumController.php`

**ANTES:**
```php
public function store(Request $request): JsonResponse
{
    // ...
    return response()->json([
        'id' => $album->id,
        // ...
    ], 201);
}
```

**DEPOIS:**
```php
use Illuminate\Http\RedirectResponse;

public function store(Request $request): RedirectResponse
{
    // ...
    return back()->with('album', [
        'id' => $album->id,
        // ...
    ]);
}
```

## Mudanças Principais

| Aspecto | Antes | Depois |
|---------|-------|--------|
| **Tipo de retorno** | `JsonResponse` | `RedirectResponse` |
| **Sucesso** | `response()->json($data, 201)` | `back()->with('key', $data)` |
| **Erro** | `response()->json($error, 400)` | `back()->withErrors($error)` |
| **Import** | `use Illuminate\Http\JsonResponse;` | `use Illuminate\Http\RedirectResponse;` |

## Como Funciona

### Fluxo de Upload (Exemplo)

1. **Frontend (Vue)**: 
   ```typescript
   router.post('/admin/media/upload', formData, {
     onSuccess: (page) => {
       const media = page.props.media; // ✅ Dados retornados
     }
   });
   ```

2. **Backend (Laravel)**:
   ```php
   return back()->with('media', $mediaData);
   ```

3. **Inertia**: Converte automaticamente para resposta Inertia compatível

### Tratamento de Erros

**Antes (JSON):**
```php
return response()->json(['message' => 'Erro'], 400);
```

**Depois (Inertia):**
```php
return back()->withErrors(['message' => 'Erro']);
```

**Frontend recebe:**
```typescript
router.post('/admin/media/upload', formData, {
  onError: (errors) => {
    console.log(errors.message); // "Erro"
  }
});
```

## Arquivos Modificados

| Arquivo | Métodos Alterados |
|---------|-------------------|
| `app/Http/Controllers/MediaController.php` | `upload()`, `destroy()` |
| `app/Http/Controllers/AlbumController.php` | `store()` |

## Verificação

### 1. Testar Upload
1. Acesse a Galeria de Mídias
2. Selecione um álbum
3. Faça upload de uma imagem
4. ✅ Deve funcionar sem erro Inertia

### 2. Testar Criação de Álbum
1. Clique em "Criar Álbum"
2. Digite um nome
3. Confirme
4. ✅ Álbum deve ser criado sem erro

### 3. Testar Exclusão
1. Selecione uma mídia
2. Clique em excluir
3. Confirme
4. ✅ Mídia deve ser excluída sem erro

### 4. Verificar Console (F12)
- Não deve haver erros sobre "plain JSON response"
- Requisições devem ter header `X-Inertia: true`

## Benefícios da Solução

✅ **Compatibilidade total** com Inertia.js  
✅ **Navegação preservada** (não recarrega página)  
✅ **Estado mantido** (scroll position, etc.)  
✅ **Erros tratados** corretamente no frontend  
✅ **Dados acessíveis** via `page.props`  

## Padrão Inertia

### Sucesso com Dados
```php
return back()->with('key', $data);
```

### Sucesso com Mensagem
```php
return back()->with('message', 'Sucesso!');
```

### Erro de Validação
```php
return back()->withErrors(['field' => 'Erro']);
```

### Erro Geral
```php
return back()->withErrors(['message' => 'Erro geral']);
```

## Referências

- [Inertia.js Responses](https://inertiajs.com/responses)
- [Inertia.js Redirects](https://inertiajs.com/redirects)
- [Laravel back() Helper](https://laravel.com/docs/redirects#redirecting-back)
