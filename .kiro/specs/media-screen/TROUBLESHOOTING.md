# Troubleshooting - Tela de Mídias

## Problema: Página em Branco ao Acessar /admin/midias

### ✅ Solução Aplicada

Adicionei um layout wrapper com header e estilos adequados ao componente MediaScreen.vue para garantir que a página seja renderizada corretamente.

### 🔧 Passos para Resolver

1. **Recompilar os Assets**
   ```bash
   npm run build
   ```

2. **Limpar Cache do Laravel**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

3. **Limpar Cache do Navegador**
   - Pressione `Ctrl + Shift + R` (ou `Cmd + Shift + R` no Mac)
   - Ou abra em modo anônimo/privado

4. **Verificar Console do Navegador**
   - Abra as Ferramentas do Desenvolvedor (F12)
   - Vá para a aba "Console"
   - Procure por erros JavaScript

## Como Acessar a Tela de Mídias

### Opção 1: URL Direta
```
http://localhost:8080/admin/midias
```

### Opção 2: Via Menu Filament (Se configurado)
1. Faça login na plataforma
2. No menu lateral, procure por "Mídia"
3. Clique em "Galeria de Mídias"

## Pré-requisitos

### 1. Usuário Autenticado
Você precisa estar logado na plataforma.

### 2. Wedding Context
Você precisa ter um casamento selecionado (current_wedding_id).

Se não tiver, o middleware redirecionará para o dashboard.

### 3. Assets Compilados
Os assets Vue.js precisam estar compilados:
```bash
npm run build
# ou para desenvolvimento:
npm run dev
```

## Verificações de Debug

### 1. Verificar se a Rota Existe
```bash
php artisan route:list | grep midias
```

Deve mostrar:
```
GET|HEAD  admin/midias .............. midias.index › MediaScreenController@index
```

### 2. Verificar se o Controller Existe
```bash
ls -la app/Http/Controllers/MediaScreenController.php
```

### 3. Verificar se o Componente Vue Existe
```bash
ls -la resources/js/Pages/MediaScreen.vue
```

### 4. Verificar se os Assets Foram Compilados
```bash
ls -la public/build/assets/ | grep MediaScreen
```

Deve mostrar arquivos como:
- `MediaScreen-[hash].js`
- `MediaScreen-[hash].css`

### 5. Verificar Logs do Laravel
```bash
tail -f storage/logs/laravel.log
```

### 6. Verificar Logs do Navegador
Abra o Console do Navegador (F12) e procure por:
- Erros 404 (arquivos não encontrados)
- Erros JavaScript
- Erros de CORS

## Problemas Comuns

### Problema: Página em Branco
**Causa:** Assets não compilados ou cache do navegador
**Solução:**
```bash
npm run build
# Limpar cache do navegador (Ctrl + Shift + R)
```

### Problema: Erro 404
**Causa:** Rota não registrada
**Solução:** Verificar se a rota está em `routes/web.php`

### Problema: Erro 500
**Causa:** Erro no controller ou middleware
**Solução:** Verificar `storage/logs/laravel.log`

### Problema: Componentes não aparecem
**Causa:** Imports incorretos ou componentes não compilados
**Solução:**
```bash
# Verificar se todos os componentes existem
ls -la resources/js/Components/MediaScreen/

# Recompilar
npm run build
```

### Problema: Redirecionamento para Dashboard
**Causa:** Sem wedding context
**Solução:**
1. Certifique-se de ter um casamento criado
2. Selecione um casamento no Filament
3. Verifique se `current_wedding_id` está definido

## Estrutura de Arquivos

### Backend (Laravel)
```
app/Http/Controllers/
├── MediaScreenController.php  ✅
├── AlbumController.php        ✅
└── MediaController.php        ✅

routes/
└── web.php                    ✅ (rota /admin/midias)

app/Http/Middleware/
└── EnsureWeddingContextForInertia.php  ✅
```

### Frontend (Vue.js)
```
resources/js/Pages/
└── MediaScreen.vue            ✅

resources/js/Components/MediaScreen/
├── AlbumList.vue             ✅
├── AlbumItem.vue             ✅
├── AlbumContent.vue          ✅
├── UploadArea.vue            ✅
├── MediaGallery.vue          ✅
├── MediaItem.vue             ✅
├── EmptyState.vue            ✅
├── ConfirmDialog.vue         ✅
├── NotificationToast.vue     ✅
└── NotificationContainer.vue ✅

resources/js/Composables/
├── useAlbums.ts              ✅
├── useMediaUpload.ts         ✅
├── useMediaGallery.ts        ✅
└── useNotifications.ts       ✅

resources/js/types/
└── media-screen.ts           ✅
```

## Comandos Úteis

### Desenvolvimento
```bash
# Iniciar servidor de desenvolvimento
npm run dev

# Compilar para produção
npm run build

# Executar testes
npm run test -- --run
php artisan test
```

### Laravel
```bash
# Limpar todos os caches
php artisan optimize:clear

# Recriar cache de configuração
php artisan config:cache

# Recriar cache de rotas
php artisan route:cache

# Executar migrations
php artisan migrate

# Executar seeders
php artisan db:seed
```

### Docker (se aplicável)
```bash
# Reiniciar containers
docker-compose restart

# Ver logs
docker-compose logs -f app

# Entrar no container
docker-compose exec app bash
```

## Contato para Suporte

Se o problema persistir após seguir todos os passos acima:

1. Verifique os logs em `storage/logs/laravel.log`
2. Verifique o console do navegador (F12)
3. Tire screenshots dos erros
4. Documente os passos que você seguiu

## Changelog

### 2026-02-02
- ✅ Adicionado layout wrapper ao MediaScreen.vue
- ✅ Adicionado header com título "Galeria de Mídias"
- ✅ Ajustados estilos para garantir renderização correta
- ✅ Recompilados assets com `npm run build`
