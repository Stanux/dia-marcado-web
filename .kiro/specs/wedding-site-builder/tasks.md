# Implementation Plan: Wedding Site Builder

## Overview

Este plano implementa o módulo de criação de sites para casamentos. A implementação segue ordem de dependências: infraestrutura → serviços → controllers → frontend. Todas as tarefas são obrigatórias, incluindo property tests para garantir corretude.

## Tasks

- [x] 1. Criar migrations de banco de dados
  - [x] 1.1 Criar migration para tabela site_layouts
    - Criar arquivo `database/migrations/xxxx_create_site_layouts_table.php`
    - Definir coluna `id` como UUID primary key
    - Definir coluna `wedding_id` como UUID com foreign key para `weddings.id` ON DELETE CASCADE
    - Definir coluna `draft_content` como JSONB NOT NULL com default '{}'
    - Definir coluna `published_content` como JSONB nullable
    - Definir coluna `slug` como VARCHAR(255) UNIQUE NOT NULL
    - Definir coluna `custom_domain` como VARCHAR(255) nullable
    - Definir coluna `access_token` como VARCHAR(255) nullable (senha simples)
    - Definir coluna `is_published` como BOOLEAN default false
    - Definir coluna `published_at` como TIMESTAMP nullable
    - Adicionar timestamps (created_at, updated_at)
    - Criar índice em `wedding_id`
    - Criar índice único em `slug`
    - _Requirements: 2.1, 2.2, 2.4, 2.5, 5.2_

  - [x] 1.2 Criar migration para tabela site_versions
    - Criar arquivo `database/migrations/xxxx_create_site_versions_table.php`
    - Definir coluna `id` como UUID primary key
    - Definir coluna `site_layout_id` como UUID com foreign key para `site_layouts.id` ON DELETE CASCADE
    - Definir coluna `user_id` como UUID com foreign key para `users.id` ON DELETE SET NULL
    - Definir coluna `content` como JSONB NOT NULL
    - Definir coluna `summary` como VARCHAR(500) NOT NULL (descrição da mudança)
    - Definir coluna `is_published` como BOOLEAN default false (marca snapshots de publicação)
    - Adicionar timestamps
    - Criar índice em `site_layout_id`
    - Criar índice composto em `site_layout_id, created_at` para ordenação
    - _Requirements: 4.1, 4.6_

  - [x] 1.3 Criar migration para tabela site_media
    - Criar arquivo `database/migrations/xxxx_create_site_media_table.php`
    - Definir coluna `id` como UUID primary key
    - Definir coluna `site_layout_id` como UUID com foreign key para `site_layouts.id` ON DELETE CASCADE
    - Definir coluna `wedding_id` como UUID com foreign key para `weddings.id` ON DELETE CASCADE
    - Definir coluna `original_name` como VARCHAR(255) NOT NULL
    - Definir coluna `path` como VARCHAR(500) NOT NULL (caminho no storage)
    - Definir coluna `disk` como VARCHAR(50) default 'local'
    - Definir coluna `size` como BIGINT NOT NULL (tamanho em bytes)
    - Definir coluna `mime_type` como VARCHAR(100) NOT NULL
    - Definir coluna `variants` como JSONB default '{}' (versões otimizadas: webp, 2x, thumbnail)
    - Adicionar timestamps
    - Criar índice em `site_layout_id`
    - Criar índice em `wedding_id`
    - _Requirements: 16.6, 16.7_

  - [x] 1.4 Criar migration para tabela site_templates
    - Criar arquivo `database/migrations/xxxx_create_site_templates_table.php`
    - Definir coluna `id` como UUID primary key
    - Definir coluna `wedding_id` como UUID nullable com foreign key para `weddings.id` ON DELETE CASCADE (null = template do sistema)
    - Definir coluna `name` como VARCHAR(100) NOT NULL
    - Definir coluna `description` como TEXT nullable
    - Definir coluna `thumbnail` como VARCHAR(500) nullable (URL da imagem de preview)
    - Definir coluna `content` como JSONB NOT NULL (estrutura completa do template)
    - Definir coluna `is_public` como BOOLEAN default false
    - Adicionar timestamps
    - Criar índice em `wedding_id`
    - Criar índice em `is_public`
    - _Requirements: 15.1, 15.4, 15.5_

  - [x] 1.5 Criar migration para tabela system_configs
    - Criar arquivo `database/migrations/xxxx_create_system_configs_table.php`
    - Definir coluna `key` como VARCHAR(100) PRIMARY KEY
    - Definir coluna `value` como JSONB NOT NULL
    - Definir coluna `description` como TEXT nullable
    - Adicionar timestamps
    - _Requirements: 21.2_

  - [x] 1.6 Criar seeder para system_configs com valores padrão
    - Criar arquivo `database/seeders/SystemConfigSeeder.php`
    - Inserir `site.max_file_size` = 10485760 (10MB em bytes)
    - Inserir `site.max_versions` = 30
    - Inserir `site.max_storage_per_wedding` = 524288000 (500MB em bytes)
    - Inserir `site.performance_threshold` = 5242880 (5MB em bytes)
    - Inserir `site.google_maps_api_key` = null
    - Inserir `site.mapbox_api_key` = null
    - Inserir `site.allowed_extensions` = ["jpg","jpeg","png","gif","webp","mp4","webm"]
    - Inserir `site.blocked_extensions` = ["exe","bat","sh","php","js","html"]
    - Inserir `site.rate_limit_attempts` = 5
    - Inserir `site.rate_limit_minutes` = 15
    - _Requirements: 21.3, 21.4_

- [x] 2. Checkpoint - Executar migrations
  - Executar `php artisan migrate`
  - Executar `php artisan db:seed --class=SystemConfigSeeder`
  - Verificar tabelas criadas no PostgreSQL
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Criar Models Eloquent
  - [x] 3.1 Criar Model SiteLayout
    - Criar arquivo `app/Models/SiteLayout.php`
    - Estender `App\Models\WeddingScopedModel` para isolamento automático por wedding
    - Usar trait `HasUuids` para UUIDs
    - Definir `$fillable`: wedding_id, draft_content, published_content, slug, custom_domain, access_token, is_published, published_at
    - Definir `$casts`: draft_content => array, published_content => array, is_published => boolean, published_at => datetime
    - Criar relacionamento `wedding()`: belongsTo Wedding
    - Criar relacionamento `versions()`: hasMany SiteVersion, ordenado por created_at desc
    - Criar relacionamento `media()`: hasMany SiteMedia
    - Criar método `isDraft()`: retorna true se draft_content != published_content
    - Criar método `getPublicUrl()`: retorna URL pública baseada no slug
    - _Requirements: 2.1, 1.6, 3.4_

  - [x] 3.2 Criar Model SiteVersion
    - Criar arquivo `app/Models/SiteVersion.php`
    - Estender `Illuminate\Database\Eloquent\Model`
    - Usar trait `HasUuids`
    - Definir `$fillable`: site_layout_id, user_id, content, summary, is_published
    - Definir `$casts`: content => array, is_published => boolean
    - Criar relacionamento `siteLayout()`: belongsTo SiteLayout
    - Criar relacionamento `user()`: belongsTo User
    - _Requirements: 4.1_

  - [x] 3.3 Criar Model SiteMedia
    - Criar arquivo `app/Models/SiteMedia.php`
    - Estender `Illuminate\Database\Eloquent\Model`
    - Usar trait `HasUuids`
    - Definir `$fillable`: site_layout_id, wedding_id, original_name, path, disk, size, mime_type, variants
    - Definir `$casts`: variants => array, size => integer
    - Criar relacionamento `siteLayout()`: belongsTo SiteLayout
    - Criar relacionamento `wedding()`: belongsTo Wedding
    - Criar método `getUrl()`: retorna URL pública do arquivo
    - Criar método `getVariantUrl(string $variant)`: retorna URL de variante específica
    - _Requirements: 16.6_

  - [x] 3.4 Criar Model SiteTemplate
    - Criar arquivo `app/Models/SiteTemplate.php`
    - Estender `Illuminate\Database\Eloquent\Model`
    - Usar trait `HasUuids`
    - Definir `$fillable`: wedding_id, name, description, thumbnail, content, is_public
    - Definir `$casts`: content => array, is_public => boolean
    - Criar relacionamento `wedding()`: belongsTo Wedding (nullable)
    - Criar scope `scopePublic($query)`: filtra is_public = true
    - Criar scope `scopeForWedding($query, $weddingId)`: filtra por wedding_id ou is_public
    - _Requirements: 15.1_

  - [x] 3.5 Criar Model SystemConfig
    - Criar arquivo `app/Models/SystemConfig.php`
    - Estender `Illuminate\Database\Eloquent\Model`
    - Definir `$primaryKey` = 'key'
    - Definir `$keyType` = 'string'
    - Definir `$incrementing` = false
    - Definir `$fillable`: key, value, description
    - Definir `$casts`: value => array
    - Criar método estático `get(string $key, mixed $default = null)`: busca config com cache
    - Criar método estático `set(string $key, mixed $value)`: atualiza config e limpa cache
    - Usar Cache::remember para otimizar leituras frequentes
    - _Requirements: 21.1, 21.5_

  - [x] 3.6 Adicionar relacionamento sites() no Model Wedding
    - Editar `app/Models/Wedding.php`
    - Adicionar método `siteLayout()`: hasOne SiteLayout
    - _Requirements: 1.6_

- [x] 4. Escrever testes de Models
  - [x] 4.1 Criar factory SiteLayoutFactory
    - Criar arquivo `database/factories/SiteLayoutFactory.php`
    - Definir definition() com dados fake para todos os campos
    - Criar state `published()`: is_published = true, published_at = now()
    - Criar state `withAccessToken()`: access_token = fake password
    - _Requirements: 2.1_

  - [x] 4.2 Criar factory SiteVersionFactory
    - Criar arquivo `database/factories/SiteVersionFactory.php`
    - Definir definition() com dados fake
    - Criar state `published()`: is_published = true
    - _Requirements: 4.1_

  - [x] 4.3 Escrever property test para unicidade de site por wedding
    - Criar arquivo `tests/Feature/Properties/SiteUniquenessPropertyTest.php`
    - Usar Pest PHP
    - Gerar 100 weddings aleatórios
    - Para cada wedding, tentar criar 2 sites
    - Verificar que segundo site falha ou substitui o primeiro
    - **Property 2: Unicidade de Site por Wedding**
    - **Validates: Requirements 1.6**

  - [x] 4.4 Escrever property test para integridade de versões
    - Criar arquivo `tests/Feature/Properties/VersionIntegrityPropertyTest.php`
    - Gerar 100 versões aleatórias
    - Verificar que todas têm user_id, created_at e summary não nulos
    - **Property 7: Integridade de Versões**
    - **Validates: Requirements 4.6**

- [x] 5. Checkpoint - Verificar Models
  - Executar `php artisan test --filter=SiteUniquenessPropertyTest`
  - Executar `php artisan test --filter=VersionIntegrityPropertyTest`
  - Verificar relacionamentos funcionando
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Criar SiteContentSchema (estrutura JSON padrão)
  - [x] 6.1 Criar classe SiteContentSchema
    - Criar arquivo `app/Services/Site/SiteContentSchema.php`
    - Definir constante VERSION = '1.0'
    - Criar método estático `getDefaultContent()`: retorna array com estrutura completa
    - Estrutura deve incluir seções: header, hero, saveTheDate, giftRegistry, rsvp, photoGallery, footer
    - Cada seção deve ter: enabled (bool), campos específicos, style (array)
    - Incluir meta: title, description, ogImage, canonical
    - Incluir theme: primaryColor, secondaryColor, fontFamily, fontSize
    - _Requirements: 2.3, 8.1-14.6_

  - [x] 6.2 Definir estrutura da seção Header
    - enabled: boolean
    - logo: { url: string, alt: string }
    - title: string
    - subtitle: string
    - showDate: boolean
    - navigation: array de { label: string, target: string, type: 'anchor'|'url'|'action' }
    - actionButton: { label: string, target: string, style: 'primary'|'secondary'|'ghost', icon: string|null }
    - style: { height: string, alignment: 'left'|'center'|'right', backgroundColor: string, sticky: boolean, overlay: { enabled: boolean, opacity: number } }
    - _Requirements: 8.1, 8.5, 8.6, 8.7_

  - [x] 6.3 Definir estrutura da seção Hero
    - enabled: boolean
    - media: { type: 'image'|'video'|'gallery', url: string, fallback: string, autoplay: boolean, loop: boolean }
    - title: string
    - subtitle: string
    - ctaPrimary: { label: string, target: string }
    - ctaSecondary: { label: string, target: string }
    - layout: 'full-bleed'|'boxed'|'split'
    - style: { overlay: { color: string, opacity: number }, textAlign: 'left'|'center'|'right', animation: 'none'|'fade'|'slide'|'zoom', animationDuration: number }
    - _Requirements: 9.1, 9.2, 9.4, 9.5_

  - [x] 6.4 Definir estrutura da seção SaveTheDate
    - enabled: boolean
    - showMap: boolean
    - mapProvider: 'google'|'mapbox'
    - mapCoordinates: { lat: number|null, lng: number|null }
    - description: string
    - showCountdown: boolean
    - countdownFormat: 'days'|'hours'|'minutes'|'full'
    - showCalendarButton: boolean
    - style: { backgroundColor: string, layout: 'inline'|'card'|'modal' }
    - _Requirements: 10.1, 10.2, 10.5, 10.6_

  - [x] 6.5 Definir estrutura da seção GiftRegistry (mockup)
    - enabled: boolean
    - title: string (default: "Lista de Presentes")
    - description: string (default: "Em breve...")
    - style: { backgroundColor: string }
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [x] 6.6 Definir estrutura da seção RSVP (mockup)
    - enabled: boolean
    - title: string (default: "Confirme sua Presença")
    - description: string
    - mockFields: array de { label: string, type: 'text'|'email'|'select'|'number' }
    - style: { backgroundColor: string }
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [x] 6.7 Definir estrutura da seção PhotoGallery
    - enabled: boolean
    - albums: { before: { title: string, photos: array }, after: { title: string, photos: array } }
    - layout: 'masonry'|'grid'|'slideshow'
    - showLightbox: boolean
    - allowDownload: boolean
    - style: { backgroundColor: string, columns: number }
    - _Requirements: 13.1, 13.5, 13.6_

  - [x] 6.8 Definir estrutura da seção Footer
    - enabled: boolean
    - socialLinks: array de { platform: string, url: string, icon: string }
    - copyrightText: string
    - copyrightYear: number|null (null = auto-preencher)
    - showPrivacyPolicy: boolean
    - privacyPolicyUrl: string
    - showBackToTop: boolean
    - style: { backgroundColor: string, textColor: string, borderTop: boolean }
    - _Requirements: 14.1, 14.3, 14.4, 14.5_

  - [x] 6.9 Criar método validate() no SiteContentSchema
    - Verificar se content tem todas as seções obrigatórias
    - Verificar se cada seção tem campos obrigatórios
    - Retornar array de erros ou array vazio se válido
    - _Requirements: 2.3_

  - [x] 6.10 Escrever property test para validação de schema JSON
    - Criar arquivo `tests/Feature/Properties/JsonSchemaPropertyTest.php`
    - Gerar 100 conteúdos aleatórios usando getDefaultContent() com modificações
    - Serializar para JSON e deserializar
    - Verificar que estrutura resultante contém todas as seções
    - **Property 8: Validação de Schema JSON do Site**
    - **Validates: Requirements 2.1, 2.3**

- [x] 7. Criar SlugGeneratorService
  - [x] 7.1 Criar interface SlugGeneratorServiceInterface
    - Criar arquivo `app/Contracts/Site/SlugGeneratorServiceInterface.php`
    - Definir método `generate(Wedding $wedding): string`
    - Definir método `ensureUnique(string $slug): string`
    - Definir método `normalize(string $text): string`
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 7.2 Implementar SlugGeneratorService
    - Criar arquivo `app/Services/Site/SlugGeneratorService.php`
    - Implementar `generate()`:
      - Buscar nomes dos couple members do wedding
      - Se 2 pessoas: "nome1-e-nome2"
      - Se 1 pessoa: "casamento-nome1"
      - Se 3+ pessoas: "nome1-nome2-e-outros"
      - Aplicar normalize() no resultado
    - Implementar `normalize()`:
      - Converter para lowercase
      - Remover acentos (iconv ou Str::ascii)
      - Substituir espaços e caracteres especiais por hífen
      - Remover hífens duplicados
      - Limitar a 100 caracteres
    - Implementar `ensureUnique()`:
      - Verificar se slug existe no banco
      - Se existir, adicionar sufixo numérico (-2, -3, etc.)
      - Retornar slug único
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 7.3 Escrever property test para geração de slug
    - Criar arquivo `tests/Feature/Properties/SlugGenerationPropertyTest.php`
    - Gerar 100 weddings com nomes aleatórios de couple members
    - Verificar que slug gerado contém parte normalizada de pelo menos um nome
    - Verificar que slug não contém caracteres especiais ou espaços
    - **Property 12: Geração de Slug a partir de Nomes**
    - **Validates: Requirements 5.1**

  - [x] 7.4 Escrever property test para unicidade de slug
    - Criar arquivo `tests/Feature/Properties/SlugUniquenessPropertyTest.php`
    - Criar 100 sites com slugs potencialmente conflitantes
    - Verificar que todos os slugs no banco são únicos
    - **Property 3: Unicidade de Slug**
    - **Validates: Requirements 5.2**

- [x] 8. Checkpoint - Verificar SlugGeneratorService
  - Executar `php artisan test --filter=SlugGenerationPropertyTest`
  - Executar `php artisan test --filter=SlugUniquenessPropertyTest`
  - Testar manualmente com nomes com acentos
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Criar ContentSanitizerService
  - [x] 9.1 Criar interface ContentSanitizerServiceInterface
    - Criar arquivo `app/Contracts/Site/ContentSanitizerServiceInterface.php`
    - Definir método `sanitize(string $content): string`
    - Definir método `sanitizeRichText(string $content): string`
    - Definir método `sanitizeArray(array $content): array`
    - _Requirements: 20.1, 20.4_

  - [x] 9.2 Implementar ContentSanitizerService
    - Criar arquivo `app/Services/Site/ContentSanitizerService.php`
    - Implementar `sanitize()`:
      - Remover todas as tags `<script>...</script>`
      - Remover atributos de eventos inline: onclick, onerror, onload, onmouseover, etc.
      - Remover URLs javascript: (href="javascript:...")
      - Registrar tentativas de injeção em Log::warning()
    - Implementar `sanitizeRichText()`:
      - Usar HTMLPurifier ou similar
      - Permitir apenas tags: b, strong, i, em, a, br, p, span
      - Permitir atributos: href (apenas http/https), class, style (limitado)
      - Remover todo o resto
    - Implementar `sanitizeArray()`:
      - Percorrer array recursivamente
      - Aplicar sanitize() em todos os valores string
    - _Requirements: 20.1, 20.2, 20.3, 20.4_

  - [x] 9.3 Escrever property test para sanitização de scripts
    - Criar arquivo `tests/Feature/Properties/SanitizationPropertyTest.php`
    - Criar generator de conteúdo malicioso:
      - Strings com `<script>alert('xss')</script>`
      - Strings com `onclick="malicious()"`
      - Strings com `onerror="hack()"`
      - Strings com `href="javascript:void(0)"`
    - Gerar 100 inputs maliciosos
    - Verificar que output sanitizado não contém nenhum código executável
    - **Property 10: Sanitização de Scripts**
    - **Validates: Requirements 20.1-20.4**

- [x] 10. Criar PlaceholderService
  - [x] 10.1 Criar interface PlaceholderServiceInterface
    - Criar arquivo `app/Contracts/Site/PlaceholderServiceInterface.php`
    - Definir método `replacePlaceholders(string $content, Wedding $wedding): string`
    - Definir método `replaceInArray(array $content, Wedding $wedding): array`
    - Definir método `getAvailablePlaceholders(): array`
    - _Requirements: 22.1-22.7_

  - [x] 10.2 Implementar PlaceholderService
    - Criar arquivo `app/Services/Site/PlaceholderService.php`
    - Definir placeholders suportados:
      - {noivo} / {noiva} → nomes dos couple members
      - {noivos} → todos os nomes separados por " e "
      - {data} → wedding_date formatada (ex: "15 de Março de 2025")
      - {data_curta} → wedding_date curta (ex: "15/03/2025")
      - {local} → venue
      - {cidade} → city
      - {estado} → state
      - {cidade_estado} → "city - state"
    - Implementar `replacePlaceholders()`:
      - Buscar couple members do wedding
      - Se 1 pessoa: {noivo} e {noiva} = mesmo nome
      - Se 2 pessoas: {noivo} = primeiro, {noiva} = segundo
      - Se 3+ pessoas: {noivos} = todos separados por vírgula e "e"
      - Substituir todos os placeholders encontrados
    - Implementar `replaceInArray()`:
      - Percorrer array recursivamente
      - Aplicar replacePlaceholders() em valores string
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 22.7_

  - [x] 10.3 Escrever property test para substituição de placeholders
    - Criar arquivo `tests/Feature/Properties/PlaceholderPropertyTest.php`
    - Gerar 100 weddings com dados aleatórios (nomes, data, local)
    - Criar conteúdo com todos os placeholders
    - Aplicar substituição
    - Verificar que nenhum placeholder {xxx} permanece no output
    - Verificar que dados do wedding aparecem no output
    - **Property 9: Substituição de Placeholders**
    - **Validates: Requirements 22.1-22.7, 7.4**

- [x] 11. Checkpoint - Verificar serviços de conteúdo
  - Executar `php artisan test --filter=SanitizationPropertyTest`
  - Executar `php artisan test --filter=PlaceholderPropertyTest`
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Criar MediaUploadService
  - [x] 12.1 Criar interface MediaUploadServiceInterface
    - Criar arquivo `app/Contracts/Site/MediaUploadServiceInterface.php`
    - Definir método `upload(UploadedFile $file, SiteLayout $site): SiteMedia`
    - Definir método `validateFile(UploadedFile $file): ValidationResult`
    - Definir método `optimizeImage(string $path): array`
    - Definir método `scanForMalware(string $path): bool`
    - Definir método `getStorageUsage(Wedding $wedding): int`
    - Definir método `delete(SiteMedia $media): bool`
    - _Requirements: 16.1-16.8_

  - [x] 12.2 Criar classe ValidationResult
    - Criar arquivo `app/Services/Site/ValidationResult.php`
    - Propriedades: bool $valid, array $errors, array $warnings
    - Métodos: isValid(), getErrors(), getWarnings(), addError(), addWarning()
    - _Requirements: 16.1_

  - [x] 12.3 Implementar MediaUploadService
    - Criar arquivo `app/Services/Site/MediaUploadService.php`
    - Implementar `validateFile()`:
      - Verificar extensão contra SystemConfig::get('site.allowed_extensions')
      - Verificar extensão contra SystemConfig::get('site.blocked_extensions')
      - Verificar tamanho contra SystemConfig::get('site.max_file_size')
      - Verificar MIME type real usando finfo_file()
      - Comparar MIME com extensão (ex: .jpg deve ser image/jpeg)
      - Retornar ValidationResult com erros específicos
    - Implementar `upload()`:
      - Chamar validateFile() primeiro
      - Gerar nome único para arquivo
      - Salvar em storage/app/sites/{wedding_id}/{filename}
      - Chamar scanForMalware()
      - Se imagem, chamar optimizeImage()
      - Criar registro SiteMedia
      - Retornar SiteMedia
    - Implementar `optimizeImage()`:
      - Usar Intervention Image ou similar
      - Gerar versão webp
      - Gerar versão 2x (se original for grande o suficiente)
      - Gerar thumbnail (300x300)
      - Comprimir original (qualidade 85%)
      - Retornar array com paths das variantes
    - Implementar `scanForMalware()`:
      - Tentar usar ClamAV se disponível
      - Se não disponível, fazer verificação básica de magic bytes
      - Retornar true se seguro, false se suspeito
    - Implementar `getStorageUsage()`:
      - Somar size de todos os SiteMedia do wedding
      - Retornar total em bytes
    - Implementar `delete()`:
      - Remover arquivo principal e variantes do storage
      - Remover registro do banco
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8_

  - [x] 12.4 Escrever property test para validação de upload
    - Criar arquivo `tests/Feature/Properties/UploadValidationPropertyTest.php`
    - Criar generator de arquivos fake:
      - Arquivos com extensões válidas e inválidas
      - Arquivos com tamanhos variados
      - Arquivos com MIME types corretos e incorretos
    - Gerar 100 arquivos aleatórios
    - Verificar que:
      - Extensões bloqueadas → rejeitado
      - Extensões não permitidas → rejeitado
      - Tamanho > max → rejeitado
      - MIME não corresponde → rejeitado
      - Arquivo válido → aceito
    - **Property 11: Validação de Upload**
    - **Validates: Requirements 16.1-16.5**

- [x] 13. Checkpoint - Verificar MediaUploadService
  - Executar `php artisan test --filter=UploadValidationPropertyTest`
  - Testar upload manual de imagem
  - Verificar geração de variantes
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Criar SiteVersionService
  - [x] 14.1 Criar interface SiteVersionServiceInterface
    - Criar arquivo `app/Contracts/Site/SiteVersionServiceInterface.php`
    - Definir método `createVersion(SiteLayout $site, array $content, User $user, string $summary): SiteVersion`
    - Definir método `getVersions(SiteLayout $site, int $limit = null): Collection`
    - Definir método `restore(SiteLayout $site, SiteVersion $version): SiteLayout`
    - Definir método `pruneOldVersions(SiteLayout $site): int`
    - Definir método `getPublishedVersions(SiteLayout $site): Collection`
    - _Requirements: 4.1-4.6_

  - [x] 14.2 Implementar SiteVersionService
    - Criar arquivo `app/Services/Site/SiteVersionService.php`
    - Implementar `createVersion()`:
      - Criar novo SiteVersion com content, user_id, summary
      - Chamar pruneOldVersions() para manter limite
      - Retornar versão criada
    - Implementar `getVersions()`:
      - Buscar versões do site ordenadas por created_at desc
      - Aplicar limite se fornecido, senão usar SystemConfig::get('site.max_versions')
    - Implementar `restore()`:
      - Copiar content da versão para site.draft_content
      - Criar nova versão com summary "Restaurado da versão de {data}"
      - Salvar site
      - Retornar site atualizado
    - Implementar `pruneOldVersions()`:
      - Contar versões do site
      - Se > max_versions, deletar as mais antigas
      - Nunca deletar versões com is_published = true
      - Retornar quantidade deletada
    - Implementar `getPublishedVersions()`:
      - Buscar versões com is_published = true
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

  - [x] 14.3 Escrever property test para limite de versões
    - Criar arquivo `tests/Feature/Properties/VersionLimitPropertyTest.php`
    - Criar site e adicionar N+10 versões (onde N = max_versions)
    - Verificar que count(versions) <= N
    - Verificar que versões mais antigas foram removidas (FIFO)
    - **Property 6: Limite de Versões (FIFO)**
    - **Validates: Requirements 4.2, 4.3**

  - [x] 14.4 Escrever property test para round-trip de restauração
    - Criar arquivo `tests/Feature/Properties/RestoreRoundTripPropertyTest.php`
    - Criar site com várias versões
    - Para cada versão, restaurar e verificar que draft_content == version.content
    - **Property 5: Round-trip de Restauração de Versão**
    - **Validates: Requirements 4.4**

- [x] 15. Criar SiteBuilderService
  - [x] 15.1 Criar interface SiteBuilderServiceInterface
    - Criar arquivo `app/Contracts/Site/SiteBuilderServiceInterface.php`
    - Definir método `create(Wedding $wedding): SiteLayout`
    - Definir método `updateDraft(SiteLayout $site, array $content, User $user): SiteLayout`
    - Definir método `publish(SiteLayout $site, User $user): SiteLayout`
    - Definir método `rollback(SiteLayout $site, User $user): SiteLayout`
    - Definir método `applyTemplate(SiteLayout $site, SiteTemplate $template): SiteLayout`
    - _Requirements: 2.3, 3.1, 3.2, 19.1_

  - [x] 15.2 Implementar SiteBuilderService
    - Criar arquivo `app/Services/Site/SiteBuilderService.php`
    - Injetar dependências: SlugGeneratorService, SiteVersionService, ContentSanitizerService, SiteValidatorService
    - Implementar `create()`:
      - Verificar se wedding já tem site (limite de 1)
      - Gerar slug usando SlugGeneratorService
      - Criar SiteLayout com draft_content = SiteContentSchema::getDefaultContent()
      - Retornar site criado
    - Implementar `updateDraft()`:
      - Sanitizar content usando ContentSanitizerService
      - Atualizar site.draft_content
      - Criar versão usando SiteVersionService
      - Salvar e retornar site
    - Implementar `publish()`:
      - Validar usando SiteValidatorService
      - Se inválido, lançar ValidationException
      - Copiar draft_content para published_content
      - Atualizar is_published = true, published_at = now()
      - Criar versão com is_published = true
      - Disparar evento SitePublished (para notificação)
      - Retornar site
    - Implementar `rollback()`:
      - Buscar última versão publicada
      - Copiar content para published_content
      - Criar versão com summary "Rollback para versão de {data}"
      - Retornar site
    - Implementar `applyTemplate()`:
      - Mesclar template.content com site.draft_content
      - Preservar dados já preenchidos (merge inteligente)
      - Criar versão com summary "Template aplicado: {template.name}"
      - Retornar site
    - _Requirements: 1.6, 2.3, 3.1, 3.2, 15.2, 15.3, 19.1, 19.3_

  - [x] 15.3 Escrever property test para round-trip de publicação
    - Criar arquivo `tests/Feature/Properties/PublishRoundTripPropertyTest.php`
    - Criar 100 sites com draft_content aleatório
    - Publicar cada site
    - Verificar que published_content == draft_content (antes da publicação)
    - **Property 4: Round-trip de Publicação**
    - **Validates: Requirements 3.2**

- [x] 16. Checkpoint - Verificar serviços de versionamento e builder
  - Executar `php artisan test --filter=VersionLimitPropertyTest`
  - Executar `php artisan test --filter=RestoreRoundTripPropertyTest`
  - Executar `php artisan test --filter=PublishRoundTripPropertyTest`
  - Ensure all tests pass, ask the user if questions arise.

- [x] 17. Criar SiteValidatorService
  - [x] 17.1 Criar interface SiteValidatorServiceInterface
    - Criar arquivo `app/Contracts/Site/SiteValidatorServiceInterface.php`
    - Definir método `validateForPublish(SiteLayout $site): ValidationResult`
    - Definir método `validateSection(string $section, array $content): ValidationResult`
    - Definir método `checkAccessibility(array $content): array`
    - Definir método `runQAChecklist(SiteLayout $site): QAResult`
    - _Requirements: 17.1-17.7_

  - [x] 17.2 Criar classe QAResult
    - Criar arquivo `app/Services/Site/QAResult.php`
    - Propriedades: bool $passed, array $checks (cada check: name, status, message, section)
    - Métodos: isPassed(), getFailedChecks(), getWarnings(), canPublish()
    - _Requirements: 17.5_

  - [x] 17.3 Implementar SiteValidatorService
    - Criar arquivo `app/Services/Site/SiteValidatorService.php`
    - Implementar `validateForPublish()`:
      - Verificar campos obrigatórios: meta.title não vazio
      - Verificar que pelo menos header ou hero está habilitado
      - Validar cada seção habilitada
      - Retornar ValidationResult
    - Implementar `validateSection()`:
      - Para cada tipo de seção, verificar campos obrigatórios
      - Header: se habilitado, title não pode ser vazio
      - Hero: se habilitado, deve ter media.url ou title
      - SaveTheDate: se showMap, deve ter coordenadas válidas
      - PhotoGallery: se habilitado, verificar que fotos têm alt
      - Retornar ValidationResult
    - Implementar `checkAccessibility()`:
      - Verificar contraste de cores (texto vs fundo) usando algoritmo WCAG
      - Verificar que imagens têm alt text
      - Retornar array de warnings com sugestões
    - Implementar `runQAChecklist()`:
      - Check 1: Imagens com alt text
      - Check 2: Links válidos (HTTP/HTTPS)
      - Check 3: Campos obrigatórios preenchidos
      - Check 4: Contraste WCAG AA
      - Check 5: Tamanho total de recursos < threshold
      - Retornar QAResult
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 18.3_

  - [x] 17.4 Escrever unit tests para SiteValidatorService
    - Criar arquivo `tests/Unit/Services/SiteValidatorServiceTest.php`
    - Testar validação de campos obrigatórios
    - Testar validação de URLs
    - Testar verificação de contraste
    - _Requirements: 17.1-17.5_

- [x] 18. Criar AccessTokenService
  - [x] 18.1 Criar interface AccessTokenServiceInterface
    - Criar arquivo `app/Contracts/Site/AccessTokenServiceInterface.php`
    - Definir método `setToken(SiteLayout $site, string $token): void`
    - Definir método `removeToken(SiteLayout $site): void`
    - Definir método `verify(SiteLayout $site, string $token): bool`
    - Definir método `isRateLimited(string $identifier): bool`
    - Definir método `recordFailedAttempt(string $identifier): void`
    - _Requirements: 6.1-6.5_

  - [x] 18.2 Implementar AccessTokenService
    - Criar arquivo `app/Services/Site/AccessTokenService.php`
    - Implementar `setToken()`:
      - Hash do token usando bcrypt ou similar
      - Salvar em site.access_token
    - Implementar `removeToken()`:
      - Definir site.access_token = null
    - Implementar `verify()`:
      - Se site.access_token é null, retornar true (público)
      - Comparar hash do token fornecido com armazenado
      - Se falhar, chamar recordFailedAttempt()
      - Retornar resultado
    - Implementar `isRateLimited()`:
      - Usar Cache para armazenar tentativas por IP/identifier
      - Verificar se tentativas >= SystemConfig::get('site.rate_limit_attempts')
      - Retornar true se bloqueado
    - Implementar `recordFailedAttempt()`:
      - Incrementar contador no Cache
      - Definir TTL = SystemConfig::get('site.rate_limit_minutes') * 60
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 18.3 Escrever property test para proteção por senha
    - Criar arquivo `tests/Feature/Properties/PasswordProtectionPropertyTest.php`
    - Gerar 100 sites com access_token aleatório
    - Verificar que acesso sem token correto é negado
    - Verificar que acesso com token correto é permitido
    - Verificar que sites sem token são públicos
    - **Property 13: Proteção por Senha**
    - **Validates: Requirements 5.5, 6.2**

- [x] 19. Checkpoint - Verificar serviços de validação e acesso
  - Executar `php artisan test --filter=SiteValidatorServiceTest`
  - Executar `php artisan test --filter=PasswordProtectionPropertyTest`
  - Ensure all tests pass, ask the user if questions arise.

- [x] 20. Criar SitePolicy (controle de acesso)
  - [x] 20.1 Criar SitePolicy
    - Criar arquivo `app/Policies/SiteLayoutPolicy.php`
    - Implementar `viewAny(User $user)`:
      - Admin: true
      - Couple no wedding atual: true
      - Organizer com permissão 'sites': true
      - Guest: false
      - Outros: false
    - Implementar `view(User $user, SiteLayout $site)`:
      - Admin: true
      - Couple no wedding do site: true
      - Organizer com permissão 'sites' no wedding do site: true
      - Outros: false
    - Implementar `create(User $user)`:
      - Admin: true
      - Couple: true
      - Outros: false
    - Implementar `update(User $user, SiteLayout $site)`:
      - Mesmo que view()
    - Implementar `publish(User $user, SiteLayout $site)`:
      - Admin: true
      - Couple no wedding do site: true
      - Organizer: false (não pode publicar)
    - Implementar `delete(User $user, SiteLayout $site)`:
      - Admin: true
      - Couple no wedding do site: true
      - Outros: false
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 20.2 Registrar SiteLayoutPolicy no AuthServiceProvider
    - Editar `app/Providers/AuthServiceProvider.php`
    - Adicionar mapeamento: SiteLayout::class => SiteLayoutPolicy::class
    - _Requirements: 1.4, 1.5_

  - [x] 20.3 Escrever property test para controle de acesso
    - Criar arquivo `tests/Feature/Properties/SiteAccessControlPropertyTest.php`
    - Gerar combinações de usuários e sites:
      - Admin tentando acessar qualquer site
      - Couple tentando acessar seu site
      - Couple tentando acessar site de outro wedding
      - Organizer com permissão tentando acessar
      - Organizer sem permissão tentando acessar
      - Guest tentando acessar
    - Verificar que permissões são aplicadas corretamente
    - **Property 1: Controle de Acesso por Perfil**
    - **Validates: Requirements 1.4, 1.5**

- [x] 21. Checkpoint - Verificar controle de acesso
  - Executar `php artisan test --filter=SiteAccessControlPropertyTest`
  - Ensure all tests pass, ask the user if questions arise.

- [x] 22. Criar Form Requests
  - [x] 22.1 Criar CreateSiteRequest
    - Criar arquivo `app/Http/Requests/Site/CreateSiteRequest.php`
    - Autorização: usar policy 'create'
    - Regras: nenhuma (site é criado com defaults)
    - _Requirements: 2.3_

  - [x] 22.2 Criar UpdateDraftRequest
    - Criar arquivo `app/Http/Requests/Site/UpdateDraftRequest.php`
    - Autorização: usar policy 'update'
    - Regras:
      - content: required, array
      - content.sections: required, array
      - summary: nullable, string, max:500
    - _Requirements: 3.1_

  - [x] 22.3 Criar PublishSiteRequest
    - Criar arquivo `app/Http/Requests/Site/PublishSiteRequest.php`
    - Autorização: usar policy 'publish'
    - Regras: nenhuma (validação é feita no service)
    - _Requirements: 3.2_

  - [x] 22.4 Criar UpdateSettingsRequest
    - Criar arquivo `app/Http/Requests/Site/UpdateSettingsRequest.php`
    - Autorização: usar policy 'update'
    - Regras:
      - slug: nullable, string, max:100, regex:/^[a-z0-9-]+$/, unique:site_layouts,slug,{id}
      - custom_domain: nullable, string, max:255, url
      - access_token: nullable, string, min:4, max:50
    - _Requirements: 5.1, 5.4, 6.1_

  - [x] 22.5 Criar UploadMediaRequest
    - Criar arquivo `app/Http/Requests/Site/UploadMediaRequest.php`
    - Autorização: usar policy 'update' no site
    - Regras:
      - file: required, file, max:{config}, mimes:{config}
    - Buscar max e mimes de SystemConfig
    - _Requirements: 16.1, 16.2_

  - [x] 22.6 Criar RestoreVersionRequest
    - Criar arquivo `app/Http/Requests/Site/RestoreVersionRequest.php`
    - Autorização: usar policy 'update'
    - Regras:
      - version_id: required, uuid, exists:site_versions,id
    - _Requirements: 4.4_

  - [x] 22.7 Criar AuthenticateSiteRequest
    - Criar arquivo `app/Http/Requests/Site/AuthenticateSiteRequest.php`
    - Autorização: true (público)
    - Regras:
      - password: required, string
    - _Requirements: 6.2_

- [x] 23. Criar SiteLayoutController
  - [x] 23.1 Criar SiteLayoutController
    - Criar arquivo `app/Http/Controllers/Api/SiteLayoutController.php`
    - Injetar SiteBuilderService, SiteVersionService
    - Implementar `index()`:
      - Buscar site do wedding atual (máximo 1)
      - Retornar site ou null
      - _Requirements: 1.2_
    - Implementar `store(CreateSiteRequest $request)`:
      - Chamar SiteBuilderService::create()
      - Retornar site criado com status 201
      - _Requirements: 2.3_
    - Implementar `show(SiteLayout $site)`:
      - Autorizar via policy
      - Retornar site com draft_content
      - _Requirements: 1.2_
    - Implementar `updateDraft(UpdateDraftRequest $request, SiteLayout $site)`:
      - Chamar SiteBuilderService::updateDraft()
      - Retornar site atualizado
      - _Requirements: 3.1_
    - Implementar `updateSettings(UpdateSettingsRequest $request, SiteLayout $site)`:
      - Atualizar slug, custom_domain, access_token
      - Retornar site atualizado
      - _Requirements: 5.1, 6.1_
    - Implementar `publish(PublishSiteRequest $request, SiteLayout $site)`:
      - Chamar SiteBuilderService::publish()
      - Retornar site publicado
      - _Requirements: 3.2_
    - Implementar `rollback(SiteLayout $site)`:
      - Autorizar via policy 'publish'
      - Chamar SiteBuilderService::rollback()
      - Retornar site
      - _Requirements: 19.1_
    - Implementar `versions(SiteLayout $site)`:
      - Autorizar via policy 'view'
      - Retornar lista de versões
      - _Requirements: 4.1_
    - Implementar `restore(RestoreVersionRequest $request, SiteLayout $site)`:
      - Buscar versão
      - Chamar SiteVersionService::restore()
      - Retornar site atualizado
      - _Requirements: 4.4_
    - Implementar `preview(SiteLayout $site)`:
      - Autorizar via policy 'view'
      - Aplicar placeholders no draft_content
      - Retornar conteúdo renderizado
      - _Requirements: 7.4_
    - Implementar `qa(SiteLayout $site)`:
      - Executar QA checklist
      - Retornar resultado
      - _Requirements: 17.5_

- [x] 24. Criar MediaController
  - [x] 24.1 Criar MediaController
    - Criar arquivo `app/Http/Controllers/Api/SiteMediaController.php`
    - Injetar MediaUploadService
    - Implementar `index(SiteLayout $site)`:
      - Autorizar via policy 'view'
      - Retornar lista de media do site
      - _Requirements: 16.6_
    - Implementar `store(UploadMediaRequest $request, SiteLayout $site)`:
      - Chamar MediaUploadService::upload()
      - Retornar media criada com URLs das variantes
      - _Requirements: 16.1-16.7_
    - Implementar `destroy(SiteLayout $site, SiteMedia $media)`:
      - Autorizar via policy 'update'
      - Chamar MediaUploadService::delete()
      - Retornar status 204
      - _Requirements: 16.6_
    - Implementar `usage(SiteLayout $site)`:
      - Retornar uso de storage do wedding
      - Retornar limite configurado
      - _Requirements: 16.8_

- [x] 25. Criar TemplateController
  - [x] 25.1 Criar TemplateController
    - Criar arquivo `app/Http/Controllers/Api/SiteTemplateController.php`
    - Implementar `index()`:
      - Buscar templates públicos + privados do wedding atual
      - Retornar lista
      - _Requirements: 15.1_
    - Implementar `show(SiteTemplate $template)`:
      - Verificar acesso (público ou do wedding)
      - Retornar template
      - _Requirements: 15.1_
    - Implementar `store(Request $request)`:
      - Criar template privado para o wedding
      - Regras: name required, content required array
      - _Requirements: 15.4_
    - Implementar `apply(SiteLayout $site, SiteTemplate $template)`:
      - Autorizar via policy 'update'
      - Chamar SiteBuilderService::applyTemplate()
      - Retornar site atualizado
      - _Requirements: 15.2, 15.3_

- [x] 26. Criar PublicSiteController
  - [x] 26.1 Criar PublicSiteController
    - Criar arquivo `app/Http/Controllers/PublicSiteController.php`
    - Injetar AccessTokenService, PlaceholderService
    - Implementar `show(string $slug)`:
      - Buscar site pelo slug
      - Se não encontrado, retornar 404
      - Se não publicado, retornar 404
      - Se tem access_token, verificar sessão
      - Se não autenticado, retornar view de senha
      - Aplicar placeholders no published_content
      - Retornar view do site público
      - _Requirements: 5.5, 5.6, 5.7_
    - Implementar `authenticate(AuthenticateSiteRequest $request, string $slug)`:
      - Buscar site pelo slug
      - Verificar rate limiting
      - Verificar senha via AccessTokenService
      - Se correto, criar sessão e redirecionar
      - Se incorreto, retornar erro
      - _Requirements: 6.2, 6.3, 6.5_
    - Implementar `calendar(string $slug)`:
      - Buscar site e wedding
      - Gerar arquivo .ics com dados do evento
      - Retornar download
      - _Requirements: 10.6_

- [x] 27. Criar SystemConfigController (Admin)
  - [x] 27.1 Criar SystemConfigController
    - Criar arquivo `app/Http/Controllers/Api/Admin/SystemConfigController.php`
    - Middleware: auth, admin
    - Implementar `index()`:
      - Retornar todas as configs com prefixo 'site.'
      - _Requirements: 21.1_
    - Implementar `update(Request $request, string $key)`:
      - Validar que key começa com 'site.'
      - Atualizar valor
      - Limpar cache
      - _Requirements: 21.5_

- [x] 28. Registrar rotas da API
  - [x] 28.1 Criar arquivo de rotas para sites
    - Criar arquivo `routes/api/sites.php`
    - Rotas autenticadas (middleware: auth:sanctum):
      - GET /api/sites → SiteLayoutController@index
      - POST /api/sites → SiteLayoutController@store
      - GET /api/sites/{site} → SiteLayoutController@show
      - PUT /api/sites/{site}/draft → SiteLayoutController@updateDraft
      - PUT /api/sites/{site}/settings → SiteLayoutController@updateSettings
      - POST /api/sites/{site}/publish → SiteLayoutController@publish
      - POST /api/sites/{site}/rollback → SiteLayoutController@rollback
      - GET /api/sites/{site}/versions → SiteLayoutController@versions
      - POST /api/sites/{site}/restore → SiteLayoutController@restore
      - GET /api/sites/{site}/preview → SiteLayoutController@preview
      - GET /api/sites/{site}/qa → SiteLayoutController@qa
    - Rotas de media:
      - GET /api/sites/{site}/media → SiteMediaController@index
      - POST /api/sites/{site}/media → SiteMediaController@store
      - DELETE /api/sites/{site}/media/{media} → SiteMediaController@destroy
      - GET /api/sites/{site}/media/usage → SiteMediaController@usage
    - Rotas de templates:
      - GET /api/templates → SiteTemplateController@index
      - GET /api/templates/{template} → SiteTemplateController@show
      - POST /api/templates → SiteTemplateController@store
      - POST /api/sites/{site}/apply-template/{template} → SiteTemplateController@apply
    - _Requirements: 1.2, 3.1, 3.2, 4.1, 4.4, 15.1, 16.6_

  - [x] 28.2 Criar arquivo de rotas para admin
    - Criar arquivo `routes/api/admin.php`
    - Middleware: auth:sanctum, admin
    - Rotas:
      - GET /api/admin/config → SystemConfigController@index
      - PUT /api/admin/config/{key} → SystemConfigController@update
    - _Requirements: 21.1, 21.5_

  - [x] 28.3 Criar arquivo de rotas públicas
    - Editar `routes/web.php` ou criar `routes/public.php`
    - Rotas públicas (sem auth):
      - GET /s/{slug} → PublicSiteController@show
      - POST /s/{slug}/auth → PublicSiteController@authenticate
      - GET /s/{slug}/calendar.ics → PublicSiteController@calendar
    - _Requirements: 5.7, 6.2, 10.6_

  - [x] 28.4 Incluir arquivos de rotas no RouteServiceProvider
    - Editar `app/Providers/RouteServiceProvider.php` ou `bootstrap/app.php`
    - Incluir routes/api/sites.php
    - Incluir routes/api/admin.php
    - _Requirements: 1.2_

- [x] 29. Checkpoint - Verificar API
  - Executar todos os testes de property
  - Testar endpoints via Postman/Insomnia:
    - Criar site
    - Atualizar draft
    - Publicar
    - Acessar site público
  - Ensure all tests pass, ask the user if questions arise.

- [x] 30. Criar sistema de notificações
  - [x] 30.1 Criar evento SitePublished
    - Criar arquivo `app/Events/SitePublished.php`
    - Propriedades: SiteLayout $site, User $publisher
    - _Requirements: 3.5_

  - [x] 30.2 Criar notification SitePublishedNotification
    - Criar arquivo `app/Notifications/SitePublishedNotification.php`
    - Canal: mail
    - Implementar `toMail()`:
      - Assunto: "Seu site de casamento foi publicado!"
      - Corpo: título do site, URL pública, data/hora
      - Botão: "Ver meu site"
    - _Requirements: 23.1, 23.2, 23.3_

  - [x] 30.3 Criar listener SendSitePublishedNotification
    - Criar arquivo `app/Listeners/SendSitePublishedNotification.php`
    - Escutar evento SitePublished
    - Buscar todos os couple members do wedding
    - Enviar notificação para cada um
    - Registrar envio em log
    - _Requirements: 23.1, 23.4_

  - [x] 30.4 Registrar evento e listener
    - Editar `app/Providers/EventServiceProvider.php`
    - Mapear SitePublished → SendSitePublishedNotification
    - _Requirements: 3.5_

  - [x] 30.5 Criar template de email
    - Criar arquivo `resources/views/emails/site-published.blade.php`
    - Layout responsivo
    - Variáveis: $siteTitle, $siteUrl, $publishedAt, $coupleNames
    - Estilo consistente com a plataforma
    - _Requirements: 23.3_

- [x] 31. Criar seeder de templates
  - [x] 31.1 Criar SiteTemplateSeeder
    - Criar arquivo `database/seeders/SiteTemplateSeeder.php`
    - Template "Clássico":
      - Cores: #d4a574 (dourado), #8b7355 (marrom)
      - Fonte: Playfair Display
      - Estilo elegante e tradicional
    - Template "Moderno":
      - Cores: #2d3436 (cinza escuro), #00b894 (verde)
      - Fonte: Montserrat
      - Estilo clean e contemporâneo
    - Template "Minimalista":
      - Cores: #000000, #ffffff
      - Fonte: Inter
      - Estilo simples e direto
    - Template "Romântico":
      - Cores: #e84393 (rosa), #fd79a8 (rosa claro)
      - Fonte: Dancing Script
      - Estilo delicado e feminino
    - Todos com is_public = true, wedding_id = null
    - _Requirements: 15.1, 15.2_

  - [x] 31.2 Executar seeder
    - Adicionar ao DatabaseSeeder
    - Executar `php artisan db:seed --class=SiteTemplateSeeder`
    - _Requirements: 15.1_

- [x] 32. Criar componentes Vue.js - Estrutura base
  - [x] 32.1 Criar página SiteEditor.vue
    - Criar arquivo `resources/js/Pages/Sites/Editor.vue`
    - Layout com sidebar (lista de seções) e área principal (editor)
    - Usar Inertia.js para receber dados do site
    - Estado local para draft_content
    - Auto-save com debounce (2 segundos)
    - Barra superior com: botão Salvar, botão Publicar, botão Preview
    - _Requirements: 1.2, 3.1_

  - [x] 32.2 Criar composable useSiteEditor
    - Criar arquivo `resources/js/Composables/useSiteEditor.js`
    - Estado: site, isDirty, isSaving, lastSaved
    - Métodos:
      - updateSection(section, data): atualiza seção no draft
      - save(): envia PUT para /api/sites/{id}/draft
      - publish(): envia POST para /api/sites/{id}/publish
      - rollback(): envia POST para /api/sites/{id}/rollback
    - Auto-save: watch no draft com debounce
    - _Requirements: 3.1, 3.2, 19.1_

  - [x] 32.3 Criar composable useVersionHistory
    - Criar arquivo `resources/js/Composables/useVersionHistory.js`
    - Estado: versions, isLoading
    - Métodos:
      - loadVersions(): GET /api/sites/{id}/versions
      - restore(versionId): POST /api/sites/{id}/restore
    - _Requirements: 4.1, 4.4_

  - [x] 32.4 Criar componente SectionSidebar.vue
    - Criar arquivo `resources/js/Components/Site/SectionSidebar.vue`
    - Lista de seções com toggle enabled/disabled
    - Indicador visual de seção ativa
    - Drag & drop para reordenar (opcional)
    - _Requirements: 8.1-14.6_

  - [x] 32.5 Criar componente SectionEditor.vue
    - Criar arquivo `resources/js/Components/Site/SectionEditor.vue`
    - Props: sectionType, content, onChange
    - Renderiza editor específico baseado no tipo
    - Emite eventos de mudança
    - _Requirements: 8.1-14.6_

- [x] 33. Criar editores de seção
  - [x] 33.1 Criar HeaderEditor.vue
    - Criar arquivo `resources/js/Components/Site/Editors/HeaderEditor.vue`
    - Campos:
      - Logo: upload de imagem ou URL
      - Título: input com suporte a placeholders
      - Subtítulo: input
      - Mostrar data: checkbox
      - Menu de navegação: lista editável de links
      - Botão de ação: label, destino, estilo
      - Sticky: checkbox
    - Configurações de estilo: altura, alinhamento, cor de fundo
    - _Requirements: 8.1, 8.5, 8.6, 8.7_

  - [x] 33.2 Criar HeroEditor.vue
    - Criar arquivo `resources/js/Components/Site/Editors/HeroEditor.vue`
    - Campos:
      - Tipo de mídia: select (imagem/vídeo/galeria)
      - URL da mídia: input ou upload
      - Fallback (para vídeo): upload de imagem
      - Autoplay/Loop: checkboxes (só para vídeo)
      - Título: input com rich text
      - Subtítulo: input com rich text
      - CTA primário: label + destino
      - CTA secundário: label + destino
      - Layout: select (full-bleed/boxed/split)
    - Configurações de estilo: overlay, alinhamento, animação
    - _Requirements: 9.1, 9.2, 9.4, 9.5_

  - [x] 33.3 Criar SaveTheDateEditor.vue
    - Criar arquivo `resources/js/Components/Site/Editors/SaveTheDateEditor.vue`
    - Campos:
      - Mostrar mapa: checkbox
      - Provedor do mapa: select (Google/Mapbox)
      - Coordenadas: inputs lat/lng ou busca por endereço
      - Descrição: textarea
      - Mostrar contador: checkbox
      - Formato do contador: select
      - Botão calendário: checkbox
    - Configurações de estilo: cor de fundo, layout
    - _Requirements: 10.1, 10.2, 10.5, 10.6_

  - [x] 33.4 Criar GiftRegistryEditor.vue (mockup)
    - Criar arquivo `resources/js/Components/Site/Editors/GiftRegistryEditor.vue`
    - Campos simples:
      - Título: input
      - Descrição: textarea
      - Cor de fundo: color picker
    - Aviso: "Este módulo será integrado com o Catálogo de Presentes"
    - _Requirements: 11.1, 11.3, 11.4_

  - [x] 33.5 Criar RsvpEditor.vue (mockup)
    - Criar arquivo `resources/js/Components/Site/Editors/RsvpEditor.vue`
    - Campos simples:
      - Título: input
      - Descrição: textarea
      - Cor de fundo: color picker
    - Preview de formulário mockado
    - Aviso: "Este módulo será integrado com Confirmação de Convidados"
    - _Requirements: 12.1, 12.3, 12.4_

  - [x] 33.6 Criar PhotoGalleryEditor.vue
    - Criar arquivo `resources/js/Components/Site/Editors/PhotoGalleryEditor.vue`
    - Campos:
      - Álbum "Antes": título + upload múltiplo de fotos
      - Álbum "Depois": título + upload múltiplo de fotos
      - Layout: select (masonry/grid/slideshow)
      - Lightbox: checkbox
      - Permitir download: checkbox
    - Para cada foto: título, legenda, alt text, privada
    - _Requirements: 13.1, 13.4, 13.5, 13.6, 13.7_

  - [x] 33.7 Criar FooterEditor.vue
    - Criar arquivo `resources/js/Components/Site/Editors/FooterEditor.vue`
    - Campos:
      - Redes sociais: lista editável (plataforma + URL)
      - Texto de copyright: input
      - Ano: input (vazio = auto)
      - Mostrar política de privacidade: checkbox
      - URL da política: input (se habilitado)
      - Botão voltar ao topo: checkbox
    - Configurações de estilo: cores, borda
    - _Requirements: 14.1, 14.3, 14.4, 14.5_

- [x] 34. Criar componentes auxiliares do editor
  - [x] 34.1 Criar RichTextEditor.vue
    - Criar arquivo `resources/js/Components/Site/RichTextEditor.vue`
    - Usar TipTap ou similar
    - Toolbar: negrito, itálico, link
    - Suporte a placeholders (mostrar como chips)
    - Sanitização client-side básica
    - _Requirements: 8.3, 20.4_

  - [x] 34.2 Criar MediaUploader.vue
    - Criar arquivo `resources/js/Components/Site/MediaUploader.vue`
    - Drag & drop zone
    - Progress bar durante upload
    - Preview da imagem após upload
    - Validação client-side (extensão, tamanho)
    - Exibir variantes geradas
    - _Requirements: 16.1-16.7_

  - [x] 34.3 Criar ColorPicker.vue
    - Criar arquivo `resources/js/Components/Site/ColorPicker.vue`
    - Input de cor com preview
    - Paleta de cores sugeridas
    - Suporte a transparência (para overlays)
    - _Requirements: 8.5, 9.5_

  - [x] 34.4 Criar PlaceholderHelper.vue
    - Criar arquivo `resources/js/Components/Site/PlaceholderHelper.vue`
    - Lista de placeholders disponíveis
    - Clique para inserir no campo ativo
    - Preview do valor real
    - _Requirements: 22.1-22.7_

  - [x] 34.5 Criar NavigationEditor.vue
    - Criar arquivo `resources/js/Components/Site/NavigationEditor.vue`
    - Lista editável de itens de menu
    - Cada item: label, tipo (âncora/URL/ação), destino
    - Drag & drop para reordenar
    - _Requirements: 8.1_

- [x] 35. Criar componentes de preview
  - [x] 35.1 Criar PreviewPanel.vue
    - Criar arquivo `resources/js/Components/Site/PreviewPanel.vue`
    - Três viewports lado a lado ou tabs: mobile (375px), tablet (768px), desktop (1280px)
    - Iframe ou div com scale para simular tamanhos
    - Watermark "RASCUNHO" quando isDraft
    - Botão "Visualizar como convidado"
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 35.2 Criar SitePreview.vue
    - Criar arquivo `resources/js/Components/Site/SitePreview.vue`
    - Renderiza todas as seções habilitadas
    - Aplica tema (cores, fontes)
    - Modo edição (com controles) vs modo convidado (sem controles)
    - _Requirements: 7.3, 7.4_

  - [x] 35.3 Criar componentes de preview por seção
    - HeaderPreview.vue
    - HeroPreview.vue
    - SaveTheDatePreview.vue (com contador funcional)
    - GiftRegistryPreview.vue (mockup)
    - RsvpPreview.vue (mockup)
    - PhotoGalleryPreview.vue (com lightbox)
    - FooterPreview.vue
    - _Requirements: 8.1-14.6_

- [x] 36. Criar componentes de publicação e histórico
  - [x] 36.1 Criar PublishDialog.vue
    - Criar arquivo `resources/js/Components/Site/PublishDialog.vue`
    - Modal de confirmação
    - Executa QA checklist antes de publicar
    - Exibe erros por seção se houver
    - Botão de publicar (desabilitado se erros críticos)
    - Opção de override (com aviso)
    - _Requirements: 17.1, 17.2, 17.6_

  - [x] 36.2 Criar QAPanel.vue
    - Criar arquivo `resources/js/Components/Site/QAPanel.vue`
    - Lista de checks com status (pass/fail/warning)
    - Ícones visuais
    - Link para item com problema
    - _Requirements: 17.5, 17.6_

  - [x] 36.3 Criar VersionHistoryPanel.vue
    - Criar arquivo `resources/js/Components/Site/VersionHistoryPanel.vue`
    - Lista de versões com: data, usuário, sumário
    - Badge para versões publicadas
    - Botão restaurar em cada versão
    - Confirmação antes de restaurar
    - _Requirements: 4.1, 4.4_

  - [x] 36.4 Criar SettingsPanel.vue
    - Criar arquivo `resources/js/Components/Site/SettingsPanel.vue`
    - Configuração de slug (com preview da URL)
    - Configuração de domínio customizado
    - Configuração de senha de acesso
    - Uso de storage (barra de progresso)
    - _Requirements: 5.1, 5.4, 6.1, 16.8_

- [x] 37. Checkpoint - Verificar componentes do editor
  - Testar editor visual no navegador
  - Verificar auto-save funcionando
  - Verificar preview responsivo
  - Ensure all tests pass, ask the user if questions arise.

- [x] 38. Criar páginas do site público
  - [x] 38.1 Criar layout PublicSiteLayout.vue
    - Criar arquivo `resources/js/Layouts/PublicSiteLayout.vue`
    - Layout limpo sem navegação da plataforma
    - Aplica tema do site (cores, fontes)
    - Meta tags dinâmicas
    - _Requirements: 5.7, 18.4_

  - [x] 38.2 Criar página PublicSite.vue
    - Criar arquivo `resources/js/Pages/Public/Site.vue`
    - Recebe published_content via Inertia
    - Renderiza seções habilitadas em ordem
    - Aplica placeholders já substituídos
    - _Requirements: 5.7_

  - [x] 38.3 Criar página PasswordGate.vue
    - Criar arquivo `resources/js/Pages/Public/PasswordGate.vue`
    - Formulário de senha simples
    - Mensagem de erro se senha incorreta
    - Mensagem de rate limit se bloqueado
    - Design elegante e consistente
    - _Requirements: 6.2, 6.5_

  - [x] 38.4 Criar componentes públicos de seção
    - Criar arquivo `resources/js/Components/Public/PublicHeader.vue`
      - Renderiza header com navegação funcional
      - Sticky se configurado
    - Criar arquivo `resources/js/Components/Public/PublicHero.vue`
      - Renderiza mídia (imagem/vídeo)
      - Animações de entrada
      - CTAs funcionais
    - Criar arquivo `resources/js/Components/Public/PublicSaveTheDate.vue`
      - Mapa embutido (Google/Mapbox)
      - Contador regressivo funcional
      - Botão de calendário
    - Criar arquivo `resources/js/Components/Public/PublicGiftRegistry.vue`
      - Renderiza mockup
    - Criar arquivo `resources/js/Components/Public/PublicRsvp.vue`
      - Renderiza mockup
    - Criar arquivo `resources/js/Components/Public/PublicPhotoGallery.vue`
      - Galeria com layout configurado
      - Lightbox funcional
      - Botão de download (se habilitado)
    - Criar arquivo `resources/js/Components/Public/PublicFooter.vue`
      - Links de redes sociais
      - Copyright
      - Botão voltar ao topo
    - _Requirements: 8.1-14.6_

  - [x] 38.5 Criar componente Countdown.vue
    - Criar arquivo `resources/js/Components/Public/Countdown.vue`
    - Props: targetDate, format
    - Atualiza a cada segundo
    - Formatos: dias, horas, minutos, completo
    - Estilo configurável
    - _Requirements: 10.5_

  - [x] 38.6 Criar componente Lightbox.vue
    - Criar arquivo `resources/js/Components/Public/Lightbox.vue`
    - Modal fullscreen para fotos
    - Navegação entre fotos
    - Zoom
    - Botão de download (se permitido)
    - _Requirements: 13.5_

- [x] 39. Criar página de templates
  - [x] 39.1 Criar página TemplateGallery.vue
    - Criar arquivo `resources/js/Pages/Sites/Templates.vue`
    - Grid de templates com thumbnails
    - Filtro: Todos / Públicos / Meus templates
    - Preview ao clicar
    - Botão "Usar este template"
    - _Requirements: 15.1, 15.2, 15.3_

  - [x] 39.2 Criar componente TemplateCard.vue
    - Criar arquivo `resources/js/Components/Site/TemplateCard.vue`
    - Thumbnail
    - Nome e descrição
    - Badge "Público" ou "Privado"
    - Hover com botão de ação
    - _Requirements: 15.1_

  - [x] 39.3 Criar modal SaveTemplateModal.vue
    - Criar arquivo `resources/js/Components/Site/SaveTemplateModal.vue`
    - Formulário: nome, descrição
    - Salva layout atual como template privado
    - _Requirements: 15.4_

- [x] 40. Checkpoint - Verificar site público
  - Testar acesso ao site público
  - Testar proteção por senha
  - Testar contador regressivo
  - Testar galeria com lightbox
  - Testar download de .ics
  - Ensure all tests pass, ask the user if questions arise.

- [x] 41. Integrar com FilamentPHP (painel admin)
  - [x] 41.1 Criar SiteLayoutResource para Filament
    - Criar arquivo `app/Filament/Resources/SiteLayoutResource.php`
    - Listar sites com: wedding, slug, status, published_at
    - Filtros: publicado/rascunho
    - Ações: ver, editar configurações
    - Link para editor Vue.js
    - _Requirements: 1.2_

  - [x] 41.2 Criar SystemConfigResource para Filament (Admin)
    - Criar arquivo `app/Filament/Resources/SystemConfigResource.php`
    - Listar configs com prefixo 'site.'
    - Edição inline de valores
    - Apenas para usuários Admin
    - _Requirements: 21.1, 21.5_

  - [x] 41.3 Criar widget de estatísticas de sites
    - Criar arquivo `app/Filament/Widgets/SiteStatsWidget.php`
    - Total de sites criados
    - Sites publicados
    - Uso médio de storage
    - _Requirements: 16.8_

- [x] 42. Criar testes de integração
  - [x] 42.1 Criar teste de fluxo completo de criação
    - Criar arquivo `tests/Feature/Site/CreateSiteFlowTest.php`
    - Autenticar como couple
    - Criar site
    - Verificar slug gerado
    - Verificar estrutura inicial
    - _Requirements: 2.3, 5.1_

  - [x] 42.2 Criar teste de fluxo de edição e publicação
    - Criar arquivo `tests/Feature/Site/EditPublishFlowTest.php`
    - Criar site
    - Atualizar draft várias vezes
    - Verificar versões criadas
    - Publicar
    - Verificar published_content
    - Verificar email enviado
    - _Requirements: 3.1, 3.2, 4.1, 23.1_

  - [x] 42.3 Criar teste de acesso público
    - Criar arquivo `tests/Feature/Site/PublicAccessTest.php`
    - Criar e publicar site
    - Acessar via slug
    - Verificar conteúdo renderizado
    - Testar com senha
    - Testar rate limiting
    - _Requirements: 5.5, 5.7, 6.2, 6.5_

  - [x] 42.4 Criar teste de upload de mídia
    - Criar arquivo `tests/Feature/Site/MediaUploadTest.php`
    - Upload de imagem válida
    - Verificar variantes geradas
    - Upload de arquivo inválido
    - Verificar rejeição
    - Verificar quota
    - _Requirements: 16.1-16.8_

  - [x] 42.5 Criar teste de permissões
    - Criar arquivo `tests/Feature/Site/PermissionsTest.php`
    - Testar acesso como Admin
    - Testar acesso como Couple
    - Testar acesso como Organizer com permissão
    - Testar acesso como Organizer sem permissão
    - Testar acesso como Guest
    - _Requirements: 1.1-1.5_

- [x] 43. Final checkpoint
  - Executar todos os testes: `php artisan test`
  - Verificar cobertura de código
  - Testar fluxo completo manualmente:
    1. Login como couple
    2. Criar site
    3. Escolher template
    4. Editar seções
    5. Fazer upload de imagens
    6. Visualizar preview
    7. Publicar
    8. Acessar site público
    9. Testar com senha
    10. Verificar email recebido
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Todas as tarefas são obrigatórias, incluindo property tests
- Property tests garantem corretude formal do sistema
- Checkpoints permitem validação incremental
- Cada task referencia requisitos específicos para rastreabilidade
- Frontend usa Vue.js 3 com Composition API
- Backend usa Laravel com services e policies
- Testes usam Pest PHP
