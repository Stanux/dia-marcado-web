# Integração da Galeria de Mídias com Filament

## Problema Identificado

A implementação inicial criou uma página Inertia.js standalone (`/admin/midias`) que renderizava corretamente o componente Vue MediaScreen, mas **não exibia o menu lateral do Filament**. Isso ocorreu porque:

1. A rota `/admin/midias` era uma página Inertia pura, fora do contexto do Filament
2. O Filament usa Livewire + Blade para suas páginas, não Inertia.js
3. Tentativas de usar iframe foram rejeitadas pelo usuário

## Solução Implementada

A solução correta segue o **padrão das outras páginas Filament do projeto** (MediaUpload, MediaSettings, WeddingSettings):

### 1. Página Filament (PHP)
**Arquivo**: `app/Filament/Pages/MediaGallery.php`

- Classe que estende `Filament\Pages\Page`
- Define navegação, ícone, grupo, label
- Método `getAlbumsProperty()` carrega dados dos álbuns
- Implementa `canAccess()` para controle de permissões
- Usa view Blade: `filament.pages.media-gallery`

### 2. View Blade
**Arquivo**: `resources/views/filament/pages/media-gallery.blade.php`

```blade
<x-filament-panels::page>
    <div id="media-gallery-app"></div>
    
    <script>
        window.__mediaGalleryData = {
            albums: @json($this->albums)
        };
    </script>
    
    @vite(['resources/js/media-gallery.js'])
</x-filament-panels::page>
```

- Usa o layout do Filament (`<x-filament-panels::page>`)
- Cria container para o app Vue
- Passa dados via `window.__mediaGalleryData`
- Carrega o bundle Vue separado

### 3. Entry Point Vue
**Arquivo**: `resources/js/media-gallery.js`

```javascript
import { createApp, h } from 'vue';
import MediaGalleryWrapper from './Components/MediaScreen/MediaGalleryWrapper.vue';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('media-gallery-app');
    const albumsData = window.__mediaGalleryData?.albums || [];
    
    const app = createApp({
        render() {
            return h(MediaGalleryWrapper, {
                albums: albumsData
            });
        }
    });
    
    app.mount(container);
});
```

- Entry point separado do app.js principal (que é para Inertia)
- Monta o componente Vue no container
- Recebe dados via `window.__mediaGalleryData`

### 4. Componente Wrapper Vue
**Arquivo**: `resources/js/Components/MediaScreen/MediaGalleryWrapper.vue`

- Versão simplificada do `MediaScreen.vue`
- Remove header e layout (Filament já fornece)
- Mantém toda a lógica de álbuns e mídias
- Usa os mesmos composables e componentes filhos

### 5. Configuração Vite
**Arquivo**: `vite.config.js`

```javascript
input: [
    'resources/css/app.css', 
    'resources/js/app.js',
    'resources/js/media-gallery.js'  // Novo entry point
]
```

## Arquivos Removidos

- ❌ `app/Filament/Pages/MediaScreen.php` (redirect temporário)
- ❌ `resources/views/filament/pages/media-screen-redirect.blade.php` (iframe temporário)

## Arquivos Mantidos

- ✅ `resources/js/Pages/MediaScreen.vue` (usado em outras rotas Inertia, se necessário)
- ✅ `app/Http/Controllers/MediaScreenController.php` (pode ser usado para API ou outras rotas)
- ✅ Rota `/admin/midias` em `routes/web.php` (pode ser removida se não for mais necessária)

## Como Funciona

1. **Usuário clica em "Galeria de Mídias" no menu Filament**
2. **Filament carrega** `MediaGallery.php` (Livewire page)
3. **Blade renderiza** o layout Filament + container Vue
4. **JavaScript carrega** `media-gallery.js` via Vite
5. **Vue monta** `MediaGalleryWrapper.vue` no container
6. **Componente Vue** usa composables e componentes filhos existentes
7. **Menu lateral Filament** permanece visível e funcional

## Vantagens da Solução

✅ **Menu lateral visível**: Usa layout nativo do Filament  
✅ **Sem iframe**: Integração direta no DOM  
✅ **Reutiliza código**: Usa componentes Vue existentes  
✅ **Padrão consistente**: Segue o padrão das outras páginas  
✅ **Permissões**: Controle via `canAccess()`  
✅ **Performance**: Bundle separado, não afeta app.js principal  

## Acesso

**URL**: `/admin/galeria-midias` (via menu Filament)  
**Permissões**: Couple, Organizer, Admin  
**Menu**: Mídia > Galeria de Mídias  

## Build

```bash
npm run build
```

Gera:
- `public/build/assets/media-gallery-*.js`
- `public/build/assets/media-gallery-*.css`

## Testes

Para testar:
1. Acesse o painel Filament
2. Clique em "Mídia" > "Galeria de Mídias"
3. Verifique se o menu lateral está visível
4. Verifique se os álbuns são carregados
5. Teste upload, exclusão, criação de álbuns

## Próximos Passos (Opcional)

- Remover rota `/admin/midias` se não for mais necessária
- Considerar remover `MediaScreenController.php` se não for usado
- Avaliar se `MediaScreen.vue` ainda é necessário ou pode ser consolidado
