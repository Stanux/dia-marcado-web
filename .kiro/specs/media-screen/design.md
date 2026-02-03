# Documento de Design - Tela de Mídias

## Visão Geral

A Tela de Mídias é uma interface Vue.js 3 integrada com Laravel via Inertia.js que permite gerenciar fotos e vídeos de casamento organizados em álbuns. O design prioriza feedback visual imediato, simplicidade e senso de controle para usuários não técnicos.

A arquitetura segue o padrão de componentes Vue.js com composables para lógica reutilizável, integração assíncrona com backend Laravel, e gerenciamento de estado reativo para garantir sincronização entre UI e dados.

## Arquitetura

### Visão Geral da Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    Layout da Aplicação                       │
│  ┌──────────┐  ┌──────────────────────────────────────┐    │
│  │  Sidebar │  │         Header (Título)              │    │
│  │   Menu   │  └──────────────────────────────────────┘    │
│  │          │  ┌──────────────────────────────────────┐    │
│  │ [Mídias] │  │     MediaScreen.vue (Página)         │    │
│  │  Outros  │  │  ┌────────┐  ┌──────────────────┐   │    │
│  │  Items   │  │  │ Album  │  │  Album Content   │   │    │
│  │          │  │  │ List   │  │  ┌────────────┐  │   │    │
│  │          │  │  │        │  │  │Upload Area │  │   │    │
│  │          │  │  │        │  │  └────────────┘  │   │    │
│  │          │  │  │        │  │  ┌────────────┐  │   │    │
│  │          │  │  │        │  │  │  Gallery   │  │   │    │
│  └──────────┘  │  └────────┘  │  └────────────┘  │   │    │
│                │                └──────────────────┘   │    │
│                └──────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Camadas da Aplicação

**Camada de Apresentação (Vue.js 3)**
- Componentes Vue responsáveis pela renderização e interação do usuário
- Gerenciamento de estado local com Composition API (ref, reactive, computed)
- Comunicação com backend via Inertia.js

**Camada de Lógica de Negócio (Composables)**
- Composables Vue para lógica reutilizável (useAlbums, useMediaUpload, useMediaGallery)
- Gerenciamento de estado compartilhado
- Validação de entrada do usuário

**Camada de Integração (Inertia.js)**
- Ponte entre Vue.js e Laravel
- Requisições HTTP assíncronas
- Gerenciamento de navegação SPA

**Camada de Backend (Laravel - Existente)**
- Controllers para endpoints de API
- Services para lógica de negócio (AlbumService, MediaService, UploadService)
- Models (Album, Media, UploadBatch)
- Jobs para processamento assíncrono de uploads
- Events para notificações em tempo real

## Componentes e Interfaces

### Componente Principal: MediaScreen.vue

**Responsabilidade:** Página principal que orquestra a tela de mídias

**Props:**
```typescript
interface MediaScreenProps {
  albums: Album[]           // Lista de álbuns carregada do backend
  selectedAlbumId?: number  // ID do álbum atualmente selecionado (opcional)
}
```

**Estado Interno:**
```typescript
interface MediaScreenState {
  selectedAlbum: Album | null
  isLoading: boolean
}
```

**Composables Utilizados:**
- `useAlbums()` - Gerenciamento de álbuns
- `useMediaUpload()` - Lógica de upload
- `useMediaGallery()` - Gerenciamento da galeria

**Template Structure:**
```vue
<template>
  <div class="media-screen">
    <div class="layout-columns">
      <AlbumList 
        :albums="albums"
        :selected-album-id="selectedAlbum?.id"
        @album-selected="handleAlbumSelection"
        @create-album="handleCreateAlbum"
      />
      <AlbumContent
        v-if="selectedAlbum"
        :album="selectedAlbum"
        @media-uploaded="handleMediaUploaded"
        @media-deleted="handleMediaDeleted"
      />
      <EmptyState v-else type="no-albums" />
    </div>
  </div>
</template>
```

### Componente: AlbumList.vue

**Responsabilidade:** Exibe lista de álbuns e permite seleção e criação

**Props:**
```typescript
interface AlbumListProps {
  albums: Album[]
  selectedAlbumId?: number
}
```

**Eventos Emitidos:**
```typescript
interface AlbumListEvents {
  'album-selected': (albumId: number) => void
  'create-album': () => void
}
```

**Template Structure:**
```vue
<template>
  <aside class="album-list">
    <div class="album-items">
      <AlbumItem
        v-for="album in albums"
        :key="album.id"
        :album="album"
        :is-selected="album.id === selectedAlbumId"
        @click="$emit('album-selected', album.id)"
      />
    </div>
    <button 
      class="create-album-btn"
      @click="$emit('create-album')"
    >
      + Novo álbum
    </button>
  </aside>
</template>
```

### Componente: AlbumItem.vue

**Responsabilidade:** Representa um item individual de álbum na lista

**Props:**
```typescript
interface AlbumItemProps {
  album: Album
  isSelected: boolean
}
```

**Template Structure:**
```vue
<template>
  <div 
    class="album-item"
    :class="{ 'selected': isSelected }"
  >
    <span class="album-name">{{ album.name }}</span>
    <span class="media-count">{{ album.media_count }}</span>
  </div>
</template>
```

### Componente: AlbumContent.vue

**Responsabilidade:** Exibe conteúdo do álbum selecionado (upload + galeria)

**Props:**
```typescript
interface AlbumContentProps {
  album: Album
}
```

**Eventos Emitidos:**
```typescript
interface AlbumContentEvents {
  'media-uploaded': (media: Media[]) => void
  'media-deleted': (mediaId: number) => void
}
```

**Template Structure:**
```vue
<template>
  <div class="album-content">
    <UploadArea
      :album-id="album.id"
      @upload-started="handleUploadStarted"
      @upload-completed="handleUploadCompleted"
      @upload-failed="handleUploadFailed"
    />
    <MediaGallery
      :media="album.media"
      @delete-media="handleDeleteMedia"
    />
  </div>
</template>
```

### Componente: UploadArea.vue

**Responsabilidade:** Área de upload com drag-and-drop e seleção de arquivos

**Props:**
```typescript
interface UploadAreaProps {
  albumId: number
}
```

**Estado Interno:**
```typescript
interface UploadAreaState {
  isDragOver: boolean
  uploadingFiles: UploadingFile[]
}

interface UploadingFile {
  file: File
  progress: number
  status: 'uploading' | 'completed' | 'failed'
  error?: string
}
```

**Eventos Emitidos:**
```typescript
interface UploadAreaEvents {
  'upload-started': (files: File[]) => void
  'upload-completed': (media: Media[]) => void
  'upload-failed': (error: UploadError) => void
}
```

**Métodos Principais:**
```typescript
function handleDragOver(event: DragEvent): void
function handleDragLeave(event: DragEvent): void
function handleDrop(event: DragEvent): void
function handleFileSelect(event: Event): void
function validateFiles(files: File[]): ValidationResult
function uploadFiles(files: File[]): Promise<void>
```

### Componente: MediaGallery.vue

**Responsabilidade:** Exibe grade responsiva de mídias com ações

**Props:**
```typescript
interface MediaGalleryProps {
  media: Media[]
}
```

**Eventos Emitidos:**
```typescript
interface MediaGalleryEvents {
  'delete-media': (mediaId: number) => void
}
```

**Template Structure:**
```vue
<template>
  <div class="media-gallery">
    <EmptyState v-if="media.length === 0" type="no-media" />
    <div v-else class="gallery-grid">
      <MediaItem
        v-for="item in media"
        :key="item.id"
        :media="item"
        @delete="handleDelete"
      />
    </div>
  </div>
</template>
```

### Componente: MediaItem.vue

**Responsabilidade:** Representa uma mídia individual na galeria

**Props:**
```typescript
interface MediaItemProps {
  media: Media
}
```

**Eventos Emitidos:**
```typescript
interface MediaItemEvents {
  'delete': (mediaId: number) => void
}
```

**Template Structure:**
```vue
<template>
  <div class="media-item">
    <img 
      v-if="media.type === 'image'"
      :src="media.thumbnail_url"
      :alt="media.filename"
      class="media-thumbnail"
    />
    <video 
      v-else
      :src="media.thumbnail_url"
      class="media-thumbnail"
    />
    <div class="media-actions">
      <button 
        class="delete-btn"
        @click="handleDeleteClick"
      >
        Excluir
      </button>
    </div>
  </div>
</template>
```

### Componente: EmptyState.vue

**Responsabilidade:** Exibe estados vazios com orientação

**Props:**
```typescript
interface EmptyStateProps {
  type: 'no-albums' | 'no-media'
}
```

**Template Structure:**
```vue
<template>
  <div class="empty-state">
    <div class="empty-icon">
      <!-- Ícone apropriado -->
    </div>
    <h3 class="empty-title">{{ title }}</h3>
    <p class="empty-message">{{ message }}</p>
    <button 
      v-if="actionLabel"
      class="empty-action"
      @click="handleAction"
    >
      {{ actionLabel }}
    </button>
  </div>
</template>
```

### Componente: ConfirmDialog.vue

**Responsabilidade:** Modal de confirmação para ações destrutivas

**Props:**
```typescript
interface ConfirmDialogProps {
  isOpen: boolean
  title: string
  message: string
  confirmLabel?: string
  cancelLabel?: string
}
```

**Eventos Emitidos:**
```typescript
interface ConfirmDialogEvents {
  'confirm': () => void
  'cancel': () => void
}
```

## Composables (Lógica Reutilizável)

### useAlbums

**Responsabilidade:** Gerenciar estado e operações de álbuns

```typescript
interface UseAlbumsReturn {
  albums: Ref<Album[]>
  selectedAlbum: Ref<Album | null>
  isLoading: Ref<boolean>
  selectAlbum: (albumId: number) => void
  createAlbum: (name: string) => Promise<Album>
  refreshAlbums: () => Promise<void>
}

function useAlbums(initialAlbums: Album[]): UseAlbumsReturn {
  const albums = ref<Album[]>(initialAlbums)
  const selectedAlbum = ref<Album | null>(null)
  const isLoading = ref(false)

  const selectAlbum = (albumId: number) => {
    selectedAlbum.value = albums.value.find(a => a.id === albumId) || null
  }

  const createAlbum = async (name: string): Promise<Album> => {
    isLoading.value = true
    try {
      const response = await router.post('/albums', { name })
      const newAlbum = response.data
      albums.value.push(newAlbum)
      return newAlbum
    } finally {
      isLoading.value = false
    }
  }

  const refreshAlbums = async (): Promise<void> => {
    isLoading.value = true
    try {
      const response = await router.get('/albums')
      albums.value = response.data
    } finally {
      isLoading.value = false
    }
  }

  return {
    albums,
    selectedAlbum,
    isLoading,
    selectAlbum,
    createAlbum,
    refreshAlbums
  }
}
```

### useMediaUpload

**Responsabilidade:** Gerenciar lógica de upload de mídias

```typescript
interface UseMediaUploadReturn {
  uploadingFiles: Ref<UploadingFile[]>
  uploadFiles: (albumId: number, files: File[]) => Promise<Media[]>
  validateFiles: (files: File[]) => ValidationResult
  cancelUpload: (fileId: string) => void
}

function useMediaUpload(): UseMediaUploadReturn {
  const uploadingFiles = ref<UploadingFile[]>([])

  const validateFiles = (files: File[]): ValidationResult => {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime']
    const maxSize = 100 * 1024 * 1024 // 100MB
    
    const invalidFiles = files.filter(file => 
      !allowedTypes.includes(file.type) || file.size > maxSize
    )

    return {
      isValid: invalidFiles.length === 0,
      invalidFiles,
      validFiles: files.filter(f => !invalidFiles.includes(f))
    }
  }

  const uploadFiles = async (albumId: number, files: File[]): Promise<Media[]> => {
    const validation = validateFiles(files)
    
    if (!validation.isValid) {
      throw new ValidationError('Alguns arquivos são inválidos', validation.invalidFiles)
    }

    const uploadPromises = validation.validFiles.map(file => uploadSingleFile(albumId, file))
    return Promise.all(uploadPromises)
  }

  const uploadSingleFile = async (albumId: number, file: File): Promise<Media> => {
    const uploadingFile: UploadingFile = {
      id: generateId(),
      file,
      progress: 0,
      status: 'uploading'
    }
    
    uploadingFiles.value.push(uploadingFile)

    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('album_id', albumId.toString())

      const response = await router.post('/media/upload', formData, {
        onUploadProgress: (progressEvent) => {
          uploadingFile.progress = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          )
        }
      })

      uploadingFile.status = 'completed'
      return response.data
    } catch (error) {
      uploadingFile.status = 'failed'
      uploadingFile.error = error.message
      throw error
    } finally {
      // Remove from uploading list after delay
      setTimeout(() => {
        uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== uploadingFile.id)
      }, 2000)
    }
  }

  const cancelUpload = (fileId: string): void => {
    // Implementation for canceling upload
    uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== fileId)
  }

  return {
    uploadingFiles,
    uploadFiles,
    validateFiles,
    cancelUpload
  }
}
```

### useMediaGallery

**Responsabilidade:** Gerenciar operações da galeria de mídias

```typescript
interface UseMediaGalleryReturn {
  media: Ref<Media[]>
  deleteMedia: (mediaId: number) => Promise<void>
  refreshMedia: (albumId: number) => Promise<void>
}

function useMediaGallery(initialMedia: Media[]): UseMediaGalleryReturn {
  const media = ref<Media[]>(initialMedia)

  const deleteMedia = async (mediaId: number): Promise<void> => {
    await router.delete(`/media/${mediaId}`)
    media.value = media.value.filter(m => m.id !== mediaId)
  }

  const refreshMedia = async (albumId: number): Promise<void> => {
    const response = await router.get(`/albums/${albumId}/media`)
    media.value = response.data
  }

  return {
    media,
    deleteMedia,
    refreshMedia
  }
}
```

## Modelos de Dados

### Album

```typescript
interface Album {
  id: number
  name: string
  media_count: number
  media: Media[]
  created_at: string
  updated_at: string
}
```

### Media

```typescript
interface Media {
  id: number
  album_id: number
  filename: string
  type: 'image' | 'video'
  mime_type: string
  size: number
  url: string
  thumbnail_url: string
  created_at: string
  updated_at: string
}
```

### UploadingFile

```typescript
interface UploadingFile {
  id: string
  file: File
  progress: number
  status: 'uploading' | 'completed' | 'failed'
  error?: string
}
```

### ValidationResult

```typescript
interface ValidationResult {
  isValid: boolean
  validFiles: File[]
  invalidFiles: File[]
  errors?: string[]
}
```

### UploadError

```typescript
interface UploadError {
  message: string
  files: File[]
  code?: string
}
```

## Propriedades de Corretude

*Uma propriedade é uma característica ou comportamento que deve ser verdadeiro em todas as execuções válidas de um sistema - essencialmente, uma declaração formal sobre o que o sistema deve fazer. Propriedades servem como ponte entre especificações legíveis por humanos e garantias de corretude verificáveis por máquina.*

### Propriedade 1: Persistência de elementos de layout
*Para qualquer* interação do usuário dentro da tela de mídias, o menu lateral e o cabeçalho superior devem permanecer visíveis e acessíveis.
**Valida: Requisitos 1.4**

### Propriedade 2: Ausência de scroll horizontal
*Para qualquer* resolução de viewport suportada, o layout não deve gerar scroll horizontal.
**Valida: Requisitos 2.4**

### Propriedade 3: Renderização completa de álbuns
*Para qualquer* conjunto de álbuns fornecido ao componente, todos os álbuns devem ser renderizados na lista com nome e contagem de mídias visíveis.
**Valida: Requisitos 3.1, 3.2**

### Propriedade 4: Sincronização de seleção de álbum
*Para qualquer* álbum selecionado pelo usuário, o sistema deve simultaneamente destacar visualmente o álbum na lista e carregar suas mídias na área de conteúdo.
**Valida: Requisitos 3.3, 3.4**

### Propriedade 5: Feedback visual em drag-and-drop
*Para qualquer* evento de arrastar arquivos sobre a área de upload, o sistema deve fornecer feedback visual indicando prontidão para receber arquivos.
**Valida: Requisitos 4.2**

### Propriedade 6: Inicialização de upload para arquivos válidos
*Para qualquer* conjunto de arquivos válidos soltos na área de upload, o sistema deve iniciar o processo de upload para todos os arquivos.
**Valida: Requisitos 4.3**

### Propriedade 7: Validação de tipo de arquivo
*Para qualquer* arquivo selecionado para upload, o sistema deve aceitar apenas arquivos de imagem (JPEG, PNG, GIF) e vídeo (MP4, QuickTime), rejeitando outros tipos com mensagem explicativa.
**Valida: Requisitos 4.5, 4.6**

### Propriedade 8: Indicadores individuais de progresso
*Para qualquer* arquivo em processo de upload, o sistema deve exibir um indicador de carregamento individual e visível.
**Valida: Requisitos 5.1, 5.2**

### Propriedade 9: Transição de estado após upload bem-sucedido
*Para qualquer* upload concluído com sucesso, o sistema deve remover o indicador de carregamento e adicionar a mídia à galeria.
**Valida: Requisitos 5.3**

### Propriedade 10: Mensagem de erro em falha de upload
*Para qualquer* upload que falhe, o sistema deve exibir mensagem de erro específica descrevendo o problema.
**Valida: Requisitos 5.4**

### Propriedade 11: Renderização completa de galeria com thumbnails
*Para qualquer* conjunto de mídias pertencentes ao álbum selecionado, todas as mídias devem ser renderizadas na galeria usando suas URLs de thumbnail.
**Valida: Requisitos 6.1, 6.2**

### Propriedade 12: Suporte a múltiplos aspect ratios
*Para qualquer* conjunto de mídias com diferentes proporções de aspecto, todas devem ser renderizadas corretamente na galeria sem distorção.
**Valida: Requisitos 6.3**

### Propriedade 13: Responsividade da grade de galeria
*Para qualquer* largura de viewport dentro dos limites suportados, a galeria deve ajustar automaticamente o número de colunas mantendo legibilidade.
**Valida: Requisitos 6.5**

### Propriedade 14: Presença de botão de exclusão
*Para qualquer* mídia renderizada na galeria, deve existir um botão de ação "Excluir" visível e acessível.
**Valida: Requisitos 7.1**

### Propriedade 15: Confirmação antes de exclusão
*Para qualquer* clique no botão "Excluir" de uma mídia, o sistema deve exibir diálogo de confirmação antes de executar a remoção.
**Valida: Requisitos 7.2**

### Propriedade 16: Remoção efetiva após confirmação
*Para qualquer* confirmação de exclusão de mídia, o sistema deve remover a mídia do álbum e atualizar a galeria.
**Valida: Requisitos 7.3**

### Propriedade 17: Sincronização de contagem após exclusão
*Para qualquer* mídia removida de um álbum, a contagem de mídias exibida no seletor de álbuns deve decrementar em 1.
**Valida: Requisitos 7.5**

### Propriedade 18: Ações em estados vazios
*Para qualquer* estado vazio exibido (sem álbuns ou sem mídias), o sistema deve fornecer pelo menos uma ação direta para resolver o estado.
**Valida: Requisitos 8.3**

### Propriedade 19: Indicador de carregamento para ações assíncronas
*Para qualquer* ação assíncrona em processamento (criar álbum, deletar mídia), o sistema deve exibir indicador de carregamento apropriado.
**Valida: Requisitos 9.2**

### Propriedade 20: Feedback de sucesso
*Para qualquer* ação concluída com sucesso, o sistema deve fornecer confirmação visual ao usuário.
**Valida: Requisitos 9.3**

### Propriedade 21: Mensagens de erro acionáveis
*Para qualquer* erro que ocorra durante operações do sistema, deve ser exibida mensagem de erro clara descrevendo o problema.
**Valida: Requisitos 9.4**


## Tratamento de Erros

### Categorias de Erro

**Erros de Validação**
- Tipo de arquivo não suportado
- Tamanho de arquivo excedido
- Nome de álbum inválido (vazio, muito longo)

**Erros de Rede**
- Falha na conexão durante upload
- Timeout de requisição
- Erro de servidor (5xx)

**Erros de Autorização**
- Usuário não autenticado
- Permissões insuficientes para operação

**Erros de Estado**
- Tentativa de upload sem álbum selecionado
- Tentativa de deletar mídia já removida

### Estratégias de Tratamento

**Validação de Entrada**
```typescript
function validateFiles(files: File[]): ValidationResult {
  const allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif',
    'video/mp4', 'video/quicktime'
  ]
  const maxSize = 100 * 1024 * 1024 // 100MB
  
  const errors: string[] = []
  const validFiles: File[] = []
  const invalidFiles: File[] = []
  
  files.forEach(file => {
    if (!allowedTypes.includes(file.type)) {
      errors.push(`${file.name}: tipo de arquivo não suportado`)
      invalidFiles.push(file)
    } else if (file.size > maxSize) {
      errors.push(`${file.name}: arquivo muito grande (máximo 100MB)`)
      invalidFiles.push(file)
    } else {
      validFiles.push(file)
    }
  })
  
  return {
    isValid: invalidFiles.length === 0,
    validFiles,
    invalidFiles,
    errors
  }
}
```

**Tratamento de Erros de Upload**
```typescript
async function uploadSingleFile(albumId: number, file: File): Promise<Media> {
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('album_id', albumId.toString())

    const response = await router.post('/media/upload', formData, {
      onUploadProgress: (progressEvent) => {
        updateProgress(file, progressEvent)
      }
    })

    return response.data
  } catch (error) {
    if (error.response) {
      // Erro de resposta do servidor
      switch (error.response.status) {
        case 413:
          throw new UploadError('Arquivo muito grande', [file], 'FILE_TOO_LARGE')
        case 415:
          throw new UploadError('Tipo de arquivo não suportado', [file], 'UNSUPPORTED_TYPE')
        case 401:
          throw new UploadError('Sessão expirada. Faça login novamente', [file], 'UNAUTHORIZED')
        case 500:
          throw new UploadError('Erro no servidor. Tente novamente', [file], 'SERVER_ERROR')
        default:
          throw new UploadError('Erro ao fazer upload', [file], 'UNKNOWN_ERROR')
      }
    } else if (error.request) {
      // Erro de rede
      throw new UploadError('Erro de conexão. Verifique sua internet', [file], 'NETWORK_ERROR')
    } else {
      // Erro desconhecido
      throw new UploadError('Erro inesperado', [file], 'UNKNOWN_ERROR')
    }
  }
}
```

**Exibição de Erros ao Usuário**
```typescript
interface ErrorNotification {
  type: 'error' | 'warning' | 'info'
  message: string
  action?: {
    label: string
    handler: () => void
  }
}

function showError(error: UploadError): void {
  const notification: ErrorNotification = {
    type: 'error',
    message: error.message,
    action: error.code === 'NETWORK_ERROR' ? {
      label: 'Tentar novamente',
      handler: () => retryUpload(error.files)
    } : undefined
  }
  
  notificationStore.show(notification)
}
```

**Recuperação de Erros**
- Uploads falhados podem ser retentados individualmente
- Erros de rede acionam retry automático com backoff exponencial
- Erros de validação são apresentados antes do upload iniciar
- Erros de autorização redirecionam para login

### Logging e Monitoramento

**Eventos a Serem Logados**
- Início e conclusão de uploads
- Falhas de upload com detalhes do erro
- Operações de criação/exclusão de álbuns
- Erros de validação

**Formato de Log**
```typescript
interface LogEntry {
  timestamp: string
  level: 'info' | 'warning' | 'error'
  action: string
  details: Record<string, any>
  userId?: number
}

function logUploadError(error: UploadError, file: File): void {
  logger.error({
    timestamp: new Date().toISOString(),
    level: 'error',
    action: 'media_upload_failed',
    details: {
      filename: file.name,
      fileSize: file.size,
      fileType: file.type,
      errorCode: error.code,
      errorMessage: error.message
    },
    userId: currentUser.value?.id
  })
}
```

## Estratégia de Testes

### Abordagem Dual de Testes

A estratégia de testes combina **testes unitários** para casos específicos e edge cases com **testes baseados em propriedades** para validar comportamentos universais. Ambos são complementares e necessários para cobertura abrangente.

**Testes Unitários** focam em:
- Exemplos específicos de comportamento correto
- Edge cases (estados vazios, limites de validação)
- Condições de erro específicas
- Pontos de integração entre componentes

**Testes Baseados em Propriedades** focam em:
- Propriedades universais que devem valer para todas as entradas
- Cobertura abrangente através de geração aleatória de dados
- Invariantes que devem ser mantidos em todas as operações

### Biblioteca de Property-Based Testing

Para Vue.js/TypeScript, utilizaremos **fast-check** como biblioteca de testes baseados em propriedades.

**Instalação:**
```bash
npm install --save-dev fast-check @fast-check/vitest
```

**Configuração:**
Cada teste de propriedade deve executar no mínimo **100 iterações** para garantir cobertura adequada através de randomização.

### Testes Unitários

**Framework:** Vitest + Vue Test Utils

**Componentes a Testar:**

**MediaScreen.vue**
```typescript
describe('MediaScreen', () => {
  it('deve renderizar lista de álbuns quando fornecidos', () => {
    const albums = [
      { id: 1, name: 'Cerimônia', media_count: 5, media: [] },
      { id: 2, name: 'Festa', media_count: 10, media: [] }
    ]
    const wrapper = mount(MediaScreen, { props: { albums } })
    expect(wrapper.findAll('.album-item')).toHaveLength(2)
  })

  it('deve exibir estado vazio quando não há álbuns', () => {
    const wrapper = mount(MediaScreen, { props: { albums: [] } })
    expect(wrapper.find('.empty-state').exists()).toBe(true)
    expect(wrapper.text()).toContain('Crie seu primeiro álbum')
  })

  it('deve selecionar álbum ao clicar', async () => {
    const albums = [{ id: 1, name: 'Cerimônia', media_count: 0, media: [] }]
    const wrapper = mount(MediaScreen, { props: { albums } })
    
    await wrapper.find('.album-item').trigger('click')
    
    expect(wrapper.find('.album-item').classes()).toContain('selected')
  })
})
```

**UploadArea.vue**
```typescript
describe('UploadArea', () => {
  it('deve adicionar classe drag-over ao arrastar arquivos', async () => {
    const wrapper = mount(UploadArea, { props: { albumId: 1 } })
    
    await wrapper.find('.upload-area').trigger('dragover')
    
    expect(wrapper.find('.upload-area').classes()).toContain('drag-over')
  })

  it('deve rejeitar arquivos de tipo não suportado', async () => {
    const wrapper = mount(UploadArea, { props: { albumId: 1 } })
    const file = new File(['content'], 'test.txt', { type: 'text/plain' })
    
    await wrapper.vm.handleFiles([file])
    
    expect(wrapper.emitted('upload-failed')).toBeTruthy()
  })

  it('deve aceitar arquivos de imagem válidos', async () => {
    const wrapper = mount(UploadArea, { props: { albumId: 1 } })
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
    
    await wrapper.vm.handleFiles([file])
    
    expect(wrapper.emitted('upload-started')).toBeTruthy()
  })
})
```

**MediaGallery.vue**
```typescript
describe('MediaGallery', () => {
  it('deve renderizar todas as mídias fornecidas', () => {
    const media = [
      { id: 1, filename: 'photo1.jpg', type: 'image', thumbnail_url: '/thumb1.jpg' },
      { id: 2, filename: 'photo2.jpg', type: 'image', thumbnail_url: '/thumb2.jpg' }
    ]
    const wrapper = mount(MediaGallery, { props: { media } })
    
    expect(wrapper.findAll('.media-item')).toHaveLength(2)
  })

  it('deve exibir estado vazio quando não há mídias', () => {
    const wrapper = mount(MediaGallery, { props: { media: [] } })
    
    expect(wrapper.find('.empty-state').exists()).toBe(true)
  })

  it('deve emitir evento delete ao clicar em excluir', async () => {
    const media = [
      { id: 1, filename: 'photo1.jpg', type: 'image', thumbnail_url: '/thumb1.jpg' }
    ]
    const wrapper = mount(MediaGallery, { props: { media } })
    
    await wrapper.find('.delete-btn').trigger('click')
    
    expect(wrapper.emitted('delete-media')).toBeTruthy()
    expect(wrapper.emitted('delete-media')[0]).toEqual([1])
  })
})
```

### Testes Baseados em Propriedades

Cada teste de propriedade deve referenciar a propriedade do documento de design usando comentário de tag.

**Formato de Tag:**
```typescript
// Feature: media-screen, Property N: [texto da propriedade]
```

**Propriedade 3: Renderização completa de álbuns**
```typescript
import fc from 'fast-check'
import { mount } from '@vue/test-utils'
import AlbumList from '@/components/AlbumList.vue'

// Feature: media-screen, Property 3: Renderização completa de álbuns
describe('Property: Renderização completa de álbuns', () => {
  it('deve renderizar todos os álbuns com nome e contagem', () => {
    fc.assert(
      fc.property(
        fc.array(fc.record({
          id: fc.integer({ min: 1 }),
          name: fc.string({ minLength: 1, maxLength: 50 }),
          media_count: fc.integer({ min: 0, max: 1000 }),
          media: fc.constant([])
        })),
        (albums) => {
          const wrapper = mount(AlbumList, { props: { albums } })
          
          // Todos os álbuns devem estar renderizados
          expect(wrapper.findAll('.album-item')).toHaveLength(albums.length)
          
          // Cada álbum deve ter nome e contagem visíveis
          albums.forEach((album, index) => {
            const item = wrapper.findAll('.album-item')[index]
            expect(item.text()).toContain(album.name)
            expect(item.text()).toContain(album.media_count.toString())
          })
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

**Propriedade 7: Validação de tipo de arquivo**
```typescript
// Feature: media-screen, Property 7: Validação de tipo de arquivo
describe('Property: Validação de tipo de arquivo', () => {
  it('deve aceitar apenas imagens e vídeos válidos', () => {
    fc.assert(
      fc.property(
        fc.array(fc.record({
          name: fc.string({ minLength: 1 }),
          type: fc.oneof(
            fc.constant('image/jpeg'),
            fc.constant('image/png'),
            fc.constant('image/gif'),
            fc.constant('video/mp4'),
            fc.constant('video/quicktime'),
            fc.constant('text/plain'),
            fc.constant('application/pdf'),
            fc.constant('audio/mp3')
          ),
          size: fc.integer({ min: 1, max: 200 * 1024 * 1024 })
        })),
        (fileSpecs) => {
          const files = fileSpecs.map(spec => 
            new File(['content'], spec.name, { type: spec.type })
          )
          
          const result = validateFiles(files)
          
          const validTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'video/mp4', 'video/quicktime'
          ]
          
          // Arquivos válidos devem ser aceitos
          const expectedValid = files.filter(f => validTypes.includes(f.type))
          expect(result.validFiles).toHaveLength(expectedValid.length)
          
          // Arquivos inválidos devem ser rejeitados
          const expectedInvalid = files.filter(f => !validTypes.includes(f.type))
          expect(result.invalidFiles).toHaveLength(expectedInvalid.length)
          
          // Deve haver mensagem de erro para cada arquivo inválido
          if (expectedInvalid.length > 0) {
            expect(result.errors.length).toBeGreaterThan(0)
          }
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

**Propriedade 11: Renderização completa de galeria com thumbnails**
```typescript
// Feature: media-screen, Property 11: Renderização completa de galeria com thumbnails
describe('Property: Renderização completa de galeria', () => {
  it('deve renderizar todas as mídias usando thumbnails', () => {
    fc.assert(
      fc.property(
        fc.array(fc.record({
          id: fc.integer({ min: 1 }),
          filename: fc.string({ minLength: 1 }),
          type: fc.oneof(fc.constant('image'), fc.constant('video')),
          thumbnail_url: fc.webUrl(),
          url: fc.webUrl()
        })),
        (media) => {
          const wrapper = mount(MediaGallery, { props: { media } })
          
          // Todas as mídias devem estar renderizadas
          expect(wrapper.findAll('.media-item')).toHaveLength(media.length)
          
          // Cada mídia deve usar thumbnail_url
          media.forEach((item, index) => {
            const mediaElement = wrapper.findAll('.media-item')[index]
            const img = mediaElement.find('img, video')
            expect(img.attributes('src')).toBe(item.thumbnail_url)
          })
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

**Propriedade 17: Sincronização de contagem após exclusão**
```typescript
// Feature: media-screen, Property 17: Sincronização de contagem após exclusão
describe('Property: Sincronização de contagem após exclusão', () => {
  it('deve decrementar contagem ao remover mídia', () => {
    fc.assert(
      fc.property(
        fc.record({
          album: fc.record({
            id: fc.integer({ min: 1 }),
            name: fc.string({ minLength: 1 }),
            media_count: fc.integer({ min: 1, max: 100 }),
            media: fc.array(fc.record({
              id: fc.integer({ min: 1 }),
              filename: fc.string({ minLength: 1 }),
              type: fc.constant('image'),
              thumbnail_url: fc.webUrl()
            }), { minLength: 1 })
          })
        }),
        async ({ album }) => {
          const initialCount = album.media_count
          const wrapper = mount(MediaScreen, {
            props: { albums: [album], selectedAlbumId: album.id }
          })
          
          // Remover primeira mídia
          const mediaToDelete = album.media[0]
          await wrapper.vm.handleMediaDeleted(mediaToDelete.id)
          
          // Contagem deve ter decrementado
          const updatedAlbum = wrapper.vm.albums.find(a => a.id === album.id)
          expect(updatedAlbum.media_count).toBe(initialCount - 1)
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

### Testes de Integração

**Fluxo Completo de Upload**
```typescript
describe('Fluxo de Upload Integrado', () => {
  it('deve completar upload de ponta a ponta', async () => {
    // Mock do backend
    mockInertiaPost('/media/upload', (data) => ({
      id: 1,
      filename: data.get('file').name,
      type: 'image',
      thumbnail_url: '/thumb.jpg',
      url: '/full.jpg'
    }))
    
    const album = { id: 1, name: 'Test', media_count: 0, media: [] }
    const wrapper = mount(MediaScreen, { props: { albums: [album] } })
    
    // Selecionar álbum
    await wrapper.find('.album-item').trigger('click')
    
    // Fazer upload
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
    await wrapper.vm.handleUpload([file])
    
    // Aguardar conclusão
    await wrapper.vm.$nextTick()
    await flushPromises()
    
    // Verificar que mídia aparece na galeria
    expect(wrapper.findAll('.media-item')).toHaveLength(1)
    
    // Verificar que contagem foi atualizada
    expect(wrapper.find('.media-count').text()).toBe('1')
  })
})
```

### Cobertura de Testes

**Metas de Cobertura:**
- Componentes: 90%+ de cobertura de linhas
- Composables: 95%+ de cobertura de linhas
- Funções de validação: 100% de cobertura

**Áreas Críticas (100% de cobertura obrigatória):**
- Validação de arquivos
- Tratamento de erros
- Sincronização de estado entre componentes
- Lógica de upload

**Execução de Testes:**
```bash
# Testes unitários
npm run test:unit

# Testes de propriedades
npm run test:property

# Todos os testes com cobertura
npm run test:coverage

# Testes em modo watch
npm run test:watch
```

### Testes de Acessibilidade

**Verificações Automáticas:**
- Todos os botões devem ter labels descritivos
- Imagens devem ter atributos alt apropriados
- Navegação por teclado deve funcionar em todos os componentes interativos
- Contraste de cores deve atender WCAG AA

**Ferramentas:**
- @axe-core/vue para testes automatizados de acessibilidade
- Testes manuais com leitores de tela

### Testes de Performance

**Métricas a Monitorar:**
- Tempo de renderização inicial da galeria
- Tempo de resposta ao selecionar álbum
- Uso de memória durante uploads múltiplos
- Tempo de atualização da UI após operações

**Limites Aceitáveis:**
- Renderização inicial: < 500ms para 100 mídias
- Seleção de álbum: < 200ms
- Atualização após upload: < 100ms
