# Correção: Imagem Quebrada Após Upload

## Problema

Após fazer upload de uma imagem, ela aparecia quebrada na galeria (ícone de imagem quebrada).

### Causa

O `MediaUploadService` estava salvando os arquivos no disco `local`, mas o `SiteMedia` model estava tentando gerar URLs usando o disco configurado no banco de dados. Como o disco estava salvo como `local`, o Laravel não conseguia gerar URLs públicas acessíveis.

**Fluxo do Problema:**
1. Upload salva arquivo em: `Storage::disk('local')` → `/storage/app/sites/{wedding_id}/{filename}`
2. Banco salva: `disk = 'local'`
3. Model tenta gerar URL: `Storage::disk('local')->url($path)` → ❌ Falha (disco local não tem URL pública)

## Solução Implementada

Mudei o disco de armazenamento de `local` para `public`, que é o disco correto para arquivos acessíveis publicamente.

### Arquivo Modificado

**`app/Services/Site/MediaUploadService.php`**

### Mudanças Aplicadas

#### 1. Salvamento do Arquivo

**ANTES:**
```php
// Store the file
Storage::disk('local')->putFileAs($directory, $file, $filename);
$fullPath = Storage::disk('local')->path($path);
```

**DEPOIS:**
```php
// Store the file on public disk
Storage::disk('public')->putFileAs($directory, $file, $filename);
$fullPath = Storage::disk('public')->path($path);
```

#### 2. Registro no Banco de Dados

**ANTES:**
```php
$media = SiteMedia::create([
    // ...
    'disk' => 'local',
    // ...
]);
```

**DEPOIS:**
```php
$media = SiteMedia::create([
    // ...
    'disk' => 'public',
    // ...
]);
```

#### 3. Geração de Variantes (Thumbnails, WebP, etc.)

**ANTES:**
```php
$variants['webp'] = str_replace(Storage::disk('local')->path(''), '', $webpPath);
$variants['thumbnail'] = str_replace(Storage::disk('local')->path(''), '', $thumbnailPath);
// ...
```

**DEPOIS:**
```php
$variants['webp'] = str_replace(Storage::disk('public')->path(''), '', $webpPath);
$variants['thumbnail'] = str_replace(Storage::disk('public')->path(''), '', $thumbnailPath);
// ...
```

## Como Funciona Agora

### Fluxo Correto

1. **Upload**: Arquivo salvo em `Storage::disk('public')` → `/storage/app/public/sites/{wedding_id}/{filename}`
2. **Banco**: `disk = 'public'`
3. **URL Gerada**: `Storage::disk('public')->url($path)` → `http://localhost:8080/storage/sites/{wedding_id}/{filename}` ✅

### Estrutura de Diretórios

```
storage/
├── app/
│   ├── public/              ← Disco 'public'
│   │   └── sites/
│   │       └── {wedding_id}/
│   │           ├── {uuid}.jpg
│   │           ├── {uuid}_thumb.jpg
│   │           └── {uuid}.webp
│   └── private/             ← Disco 'local'
└── logs/
```

### Symlink Público

O Laravel cria um symlink de `public/storage` → `storage/app/public`:

```bash
public/storage → ../storage/app/public
```

Isso permite que arquivos em `storage/app/public` sejam acessíveis via HTTP em `/storage/*`.

## Verificação

### 1. Verificar Symlink

```bash
ls -la public/storage
```

Deve mostrar: `public/storage -> ../storage/app/public`

Se não existir, criar com:
```bash
php artisan storage:link
```

### 2. Testar Upload

1. Acesse a Galeria de Mídias
2. Faça upload de uma imagem
3. ✅ A imagem deve aparecer corretamente (não quebrada)

### 3. Verificar URL Gerada

Inspecione a imagem no navegador (F12 → Elements):

**ANTES (Quebrado):**
```html
<img src="/storage/app/sites/{wedding_id}/{filename}.jpg">
<!-- ❌ Caminho incorreto -->
```

**DEPOIS (Correto):**
```html
<img src="/storage/sites/{wedding_id}/{filename}.jpg">
<!-- ✅ Caminho correto -->
```

### 4. Verificar Arquivo no Servidor

```bash
# Arquivo deve estar em:
ls -la storage/app/public/sites/{wedding_id}/

# E acessível via:
curl http://localhost:8080/storage/sites/{wedding_id}/{filename}.jpg
```

## Discos do Laravel

| Disco | Caminho | URL Pública | Uso |
|-------|---------|-------------|-----|
| `local` | `storage/app/` | ❌ Não | Arquivos privados |
| `public` | `storage/app/public/` | ✅ Sim | Arquivos públicos (imagens, etc.) |
| `s3` | AWS S3 | ✅ Sim | Cloud storage |

## Arquivos Existentes (Migração)

Se houver arquivos já salvos com `disk = 'local'`, você pode migrá-los:

### Opção 1: Script de Migração

```php
// Migrar arquivos existentes
$media = SiteMedia::where('disk', 'local')->get();

foreach ($media as $item) {
    $oldPath = $item->path;
    $newPath = $oldPath;
    
    // Copiar arquivo
    if (Storage::disk('local')->exists($oldPath)) {
        $content = Storage::disk('local')->get($oldPath);
        Storage::disk('public')->put($newPath, $content);
        
        // Atualizar registro
        $item->update(['disk' => 'public']);
        
        // Deletar arquivo antigo (opcional)
        Storage::disk('local')->delete($oldPath);
    }
}
```

### Opção 2: Mover Manualmente

```bash
# Mover arquivos
mv storage/app/sites storage/app/public/

# Atualizar banco de dados
php artisan tinker
>>> SiteMedia::where('disk', 'local')->update(['disk' => 'public']);
```

## Benefícios da Solução

✅ **URLs públicas funcionam** corretamente  
✅ **Imagens aparecem** na galeria  
✅ **Thumbnails funcionam** (variantes)  
✅ **Padrão Laravel** seguido corretamente  
✅ **Performance** (sem processamento extra)  

## Notas de Segurança

- ✅ Arquivos em `public` são acessíveis via HTTP (correto para imagens)
- ✅ Validação de tipo de arquivo já implementada
- ✅ Scan de malware já implementado
- ✅ Extensões perigosas bloqueadas (php, exe, sh, etc.)
- ⚠️ Se precisar de arquivos privados, use disco `local` + rotas autenticadas

## Referências

- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [Public Disk](https://laravel.com/docs/filesystem#the-public-disk)
- [Storage Link](https://laravel.com/docs/filesystem#the-public-disk)


---

## ATUALIZAÇÃO: Problema de Permissões Nginx (RESOLVIDO ✅)

### Problema Adicional Encontrado

Após corrigir o disco de armazenamento, as imagens ainda apareciam quebradas com erro **403 Forbidden** do Nginx.

### Diagnóstico

```bash
# Teste de acesso
curl -I http://127.0.0.1:8080/storage/sites/{wedding_id}/{filename}.png
# Resultado: HTTP/1.1 403 Forbidden

# Logs do Nginx mostravam:
# [error] open() "/var/www/html/public/storage/..." failed (13: Permission denied)
```

### Causa Raiz

O diretório `/var/www/html/storage/app` tinha permissões muito restritivas:

```bash
drwx------  # 700 - apenas o dono (UID 1000) pode acessar
```

O Nginx roda como usuário `nginx` e não conseguia **navegar** pelos diretórios para chegar aos arquivos, mesmo que os arquivos em si tivessem permissões 644.

### Solução Aplicada

```bash
# Permitir que outros usuários possam navegar pelo diretório
docker-compose exec php chmod 755 /var/www/html/storage/app

# Garantir que subdiretórios também sejam acessíveis
docker-compose exec php chmod -R 755 /var/www/html/storage/app/public

# Garantir que arquivos sejam legíveis
docker-compose exec php find /var/www/html/storage/app/public -type f -exec chmod 644 {} \;
```

### Configuração Nginx

Adicionado location block específico para servir arquivos de storage:

```nginx
# Serve storage files directly - MUST come before location /
location ~ ^/storage/(.*)$ {
    alias /var/www/html/storage/app/public/$1;
    expires 30d;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

### Resultado

```bash
curl -I http://127.0.0.1:8080/storage/sites/{wedding_id}/{filename}.png
# HTTP/1.1 200 OK ✅
# Content-Type: image/png
# Content-Length: 95502
```

### Permissões Corretas

```
storage/
├── app/                    # 755 (drwxr-xr-x)
│   └── public/             # 755 (drwxr-xr-x)
│       └── sites/          # 755 (drwxr-xr-x)
│           └── {wedding}/  # 755 (drwxr-xr-x)
│               └── *.png   # 644 (-rw-r--r--)
```

### Para Garantir Permissões em Futuros Uploads

Adicionar ao `MediaUploadService` após salvar arquivos:

```php
// Garantir permissões corretas
chmod(Storage::disk('public')->path($path), 0644);
```

Ou configurar umask no PHP:

```php
// No início do método upload()
$oldUmask = umask(0022); // Garante 755 para diretórios, 644 para arquivos

try {
    // ... código de upload ...
} finally {
    umask($oldUmask); // Restaura umask original
}
```

### Verificação Final

✅ Imagens carregam corretamente na galeria  
✅ Thumbnails aparecem  
✅ Nginx serve arquivos com 200 OK  
✅ Cache headers configurados (30 dias)  
✅ Permissões corretas aplicadas  

**Status: PROBLEMA TOTALMENTE RESOLVIDO** 🎉
