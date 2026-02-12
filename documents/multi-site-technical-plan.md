# Plano Técnico — Múltiplos Sites por Casal (com múltiplos publicados)

Data: 2026-02-12
Responsável: Codex

## Objetivo
Permitir que um mesmo casamento tenha **mais de um site**, com a possibilidade de **múltiplos sites publicados simultaneamente**.

## Situação Atual (resumo)
O sistema foi estruturado assumindo **1 site por casamento**. O banco não impede múltiplos sites, mas o código e a UI bloqueiam ou redirecionam para o primeiro site.

## Pontos de Bloqueio (identificados)
1. **Criação de site**
   - `app/Services/Site/SiteBuilderService.php`: método `create()` impede novo site se já existe.
2. **Rotas Web**
   - `routes/web.php`: `/admin/sites/create` redireciona para o primeiro site existente do casamento.
3. **API**
   - `app/Http/Controllers/Api/SiteLayoutController.php#index`: retorna apenas um site (o primeiro).
4. **UI Filament (Admin)**
   - `app/Filament/Resources/SiteLayoutResource/Pages/ListSiteLayouts.php`: botão “Criar Site” só aparece se não existir site.
   - `app/Filament/Pages/SiteEditor.php`: sem `site` na rota, sempre cria/usa site único.
5. **Modelos**
   - `app/Models/Wedding.php`: relação `siteLayout(): HasOne`.
6. **Widget**
   - `app/Filament/Widgets/SiteStatsWidget.php`: assume um site (usa `first()`).

## Estrutura já compatível
- `site_layouts` **não tem unique** em `wedding_id`, permitindo múltiplos sites.
- `slug` é **globalmente único**, já evita colisão de URL.
- Publicação é **por site** (`is_published`), então múltiplos publicados são tecnicamente possíveis.
- Mídia e versões são por `site_layout_id`.

## Requisitos Funcionais
1. Casal pode criar **mais de um site**.
2. Vários sites podem estar **publicados simultaneamente**.
3. Editor deve permitir selecionar **qual site editar**.
4. Slug continua único.
5. (Opcional) `custom_domain` deve ser único globalmente para evitar conflito.

## Mudanças Necessárias (lista técnica)

### 1) Modelos e Relacionamentos
- `app/Models/Wedding.php`
  - trocar `siteLayout(): HasOne` por `sites(): HasMany`.
  - adaptar chamadas que usam `siteLayout()`.

### 2) Serviço de Criação
- `app/Services/Site/SiteBuilderService.php`
  - remover bloqueio de “1 site por casamento”.

### 3) Rotas / Fluxo Web
- `routes/web.php`
  - `/admin/sites/create`: não redirecionar para site existente; criar novo site sempre.
  - `/admin/sites/{site}/edit`: manter.
  - `/admin/sites/{site}/templates`: manter.

### 4) API
- `app/Http/Controllers/Api/SiteLayoutController.php#index`
  - retornar **lista** de sites do casamento.
  - ajustar front/consumo.

### 5) UI Admin (Filament)
- `app/Filament/Resources/SiteLayoutResource/Pages/ListSiteLayouts.php`
  - permitir botão “Criar Site” sempre que usuário tiver permissão.
  - opcional: mover criação para um botão explícito + confirmação.

- `app/Filament/Pages/SiteEditor.php`
  - se nenhum `site` foi passado, redirecionar para **tela de seleção** (ou listagem) em vez de criar/usar único.

- `app/Filament/Widgets/SiteStatsWidget.php`
  - atualizar para múltiplos sites (status agregado ou lista parcial).

### 6) Editor Vue (UX)
- `resources/js/Pages/Sites/Editor.vue`
  - precisa de um fluxo para **selecionar site** a editar.
  - ideal: nova tela “Meus Sites” / lista com botões **Editar** / **Publicar**.

### 7) Validações
- `app/Http/Requests/Site/UpdateSettingsRequest.php`
  - adicionar `unique` para `custom_domain` (global), se necessário.

## Sugestões de UX
- Página “Meus Sites” (listagem) com:
  - nome (slug), status publicado, data de publicação
  - botões: Editar, Ver Site, Publicar/Despublicar
  - botão “Criar novo site”

## Considerações de Dados
- Se existir `custom_domain` em mais de um site, pode conflitar — definir regra.
- Múltiplos sites publicados podem causar confusão ao usuário final: precisa ficar claro no painel.

## Estimativa de Esforço (execução)

### MVP (backend + liberar múltiplos)
- Remover bloqueios + API lista + relações.
- Sem UX nova (usar Filament list).
- **Estimativa:** 0,5–1 dia.

### Completo (UX + seleção de site + ajustes de UI)
- Listagem/seleção no editor.
- Atualização de widgets e fluxos.
- Validações adicionais.
- **Estimativa:** 2–4 dias.

## Próximos Passos Recomendados
1. Definir se `custom_domain` deve ser único globalmente.
2. Definir limite (se haverá limite máximo de sites por casamento).
3. Escolher UX desejada para seleção/gestão de múltiplos sites.

