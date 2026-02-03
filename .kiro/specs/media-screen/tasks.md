# Plano de Implementação: Tela de Mídias

## Visão Geral

Este plano implementa a Tela de Mídias para gerenciamento de fotos e vídeos de casamento, organizados em álbuns. A implementação será feita em Vue.js 3 com TypeScript, integrada com Laravel via Inertia.js, seguindo uma abordagem incremental com validação contínua através de testes.

## Tarefas

- [x] 1. Configurar estrutura base e tipos TypeScript
  - Criar interfaces TypeScript para Album, Media, UploadingFile, ValidationResult e UploadError
  - Configurar tipos para props e eventos dos componentes
  - Configurar fast-check para testes baseados em propriedades
  - _Requisitos: 10.1, 10.2_

- [ ] 2. Implementar composables de lógica de negócio
  - [x] 2.1 Criar composable useAlbums
    - Implementar gerenciamento de estado de álbuns (ref, computed)
    - Implementar funções selectAlbum, createAlbum, refreshAlbums
    - Integrar com Inertia.js para comunicação com backend
    - _Requisitos: 3.3, 3.4, 3.6_
  
  - [ ]* 2.2 Escrever testes de propriedade para useAlbums
    - **Propriedade 4: Sincronização de seleção de álbum**
    - **Valida: Requisitos 3.3, 3.4**
  
  - [x] 2.3 Criar composable useMediaUpload
    - Implementar validateFiles com validação de tipo e tamanho
    - Implementar uploadFiles com suporte a múltiplos arquivos
    - Implementar uploadSingleFile com tracking de progresso
    - Implementar tratamento de erros de upload
    - _Requisitos: 4.3, 4.5, 4.6, 5.1, 5.2, 5.3, 5.4_
  
  - [ ]* 2.4 Escrever teste de propriedade para validação de arquivos
    - **Propriedade 7: Validação de tipo de arquivo**
    - **Valida: Requisitos 4.5, 4.6**
  
  - [ ]* 2.5 Escrever testes unitários para useMediaUpload
    - Testar rejeição de arquivos inválidos (tipo, tamanho)
    - Testar aceitação de arquivos válidos
    - Testar tratamento de erros de rede
    - _Requisitos: 4.5, 4.6, 5.4_
  
  - [x] 2.6 Criar composable useMediaGallery
    - Implementar gerenciamento de estado de mídias
    - Implementar deleteMedia com integração backend
    - Implementar refreshMedia
    - _Requisitos: 7.3, 7.5_

- [ ] 3. Implementar componentes de lista de álbuns
  - [x] 3.1 Criar componente AlbumItem.vue
    - Implementar template com nome e contagem de mídias
    - Implementar estilos com estado selected
    - Adicionar estados hover e active
    - _Requisitos: 3.2, 3.3_
  
  - [x] 3.2 Criar componente AlbumList.vue
    - Implementar lista vertical de álbuns usando AlbumItem
    - Implementar botão "Novo álbum" como último item
    - Implementar emissão de eventos album-selected e create-album
    - Aplicar estilos com largura fixa
    - _Requisitos: 3.1, 3.2, 3.5, 3.6_
  
  - [ ]* 3.3 Escrever teste de propriedade para renderização de álbuns
    - **Propriedade 3: Renderização completa de álbuns**
    - **Valida: Requisitos 3.1, 3.2**
  
  - [ ]* 3.4 Escrever testes unitários para AlbumList
    - Testar renderização de lista vazia
    - Testar clique em álbum emite evento correto
    - Testar clique em "Novo álbum" emite evento
    - _Requisitos: 3.1, 3.5, 3.6_

- [ ] 4. Implementar componente de upload
  - [x] 4.1 Criar componente UploadArea.vue
    - Implementar área de drag-and-drop com eventos dragover, dragleave, drop
    - Implementar input file oculto com click handler
    - Implementar feedback visual para estado isDragOver
    - Implementar lista de arquivos em upload com indicadores de progresso
    - Integrar com useMediaUpload composable
    - _Requisitos: 4.1, 4.2, 4.3, 4.4, 5.1, 5.2_
  
  - [ ]* 4.2 Escrever teste de propriedade para feedback de drag-and-drop
    - **Propriedade 5: Feedback visual em drag-and-drop**
    - **Valida: Requisitos 4.2**
  
  - [ ]* 4.3 Escrever teste de propriedade para inicialização de upload
    - **Propriedade 6: Inicialização de upload para arquivos válidos**
    - **Valida: Requisitos 4.3**
  
  - [ ]* 4.4 Escrever testes unitários para UploadArea
    - Testar adição de classe drag-over ao arrastar
    - Testar remoção de classe ao sair da área
    - Testar abertura de seletor ao clicar
    - Testar exibição de indicadores de progresso
    - _Requisitos: 4.2, 4.4, 5.1_

- [x] 5. Checkpoint - Validar upload e álbuns
  - Garantir que todos os testes passam
  - Verificar integração entre composables e componentes
  - Perguntar ao usuário se há dúvidas ou ajustes necessários

- [ ] 6. Implementar componentes de galeria
  - [x] 6.1 Criar componente MediaItem.vue
    - Implementar renderização de thumbnail (img para imagens, video para vídeos)
    - Implementar botão "Excluir" com hover state
    - Implementar emissão de evento delete
    - Aplicar estilos para suportar diferentes aspect ratios
    - _Requisitos: 6.2, 6.3, 7.1_
  
  - [x] 6.2 Criar componente MediaGallery.vue
    - Implementar grade responsiva usando CSS Grid
    - Renderizar MediaItem para cada mídia
    - Implementar estado vazio quando não há mídias
    - Integrar com useMediaGallery composable
    - _Requisitos: 6.1, 6.2, 6.4, 6.5, 8.2_
  
  - [ ]* 6.3 Escrever teste de propriedade para renderização de galeria
    - **Propriedade 11: Renderização completa de galeria com thumbnails**
    - **Valida: Requisitos 6.1, 6.2**
  
  - [ ]* 6.4 Escrever teste de propriedade para aspect ratios
    - **Propriedade 12: Suporte a múltiplos aspect ratios**
    - **Valida: Requisitos 6.3**
  
  - [ ]* 6.5 Escrever teste de propriedade para responsividade
    - **Propriedade 13: Responsividade da grade de galeria**
    - **Valida: Requisitos 6.5**
  
  - [ ]* 6.6 Escrever testes unitários para MediaGallery
    - Testar renderização de lista vazia (empty state)
    - Testar renderização de múltiplas mídias
    - Testar emissão de evento delete
    - _Requisitos: 6.1, 7.1, 8.2_

- [ ] 7. Implementar funcionalidade de exclusão de mídias
  - [x] 7.1 Criar componente ConfirmDialog.vue
    - Implementar modal de confirmação com título e mensagem
    - Implementar botões de confirmar e cancelar
    - Implementar emissão de eventos confirm e cancel
    - Aplicar estilos com overlay e animações
    - _Requisitos: 7.2_
  
  - [x] 7.2 Integrar confirmação de exclusão no MediaItem
    - Adicionar estado local para controlar exibição do diálogo
    - Implementar handler para abrir confirmação ao clicar em excluir
    - Implementar handler para executar exclusão após confirmação
    - _Requisitos: 7.2, 7.3_
  
  - [ ]* 7.3 Escrever teste de propriedade para presença de botão excluir
    - **Propriedade 14: Presença de botão de exclusão**
    - **Valida: Requisitos 7.1**
  
  - [ ]* 7.4 Escrever teste de propriedade para confirmação
    - **Propriedade 15: Confirmação antes de exclusão**
    - **Valida: Requisitos 7.2**
  
  - [ ]* 7.5 Escrever teste de propriedade para remoção efetiva
    - **Propriedade 16: Remoção efetiva após confirmação**
    - **Valida: Requisitos 7.3**
  
  - [ ]* 7.6 Escrever teste de propriedade para sincronização de contagem
    - **Propriedade 17: Sincronização de contagem após exclusão**
    - **Valida: Requisitos 7.5**

- [ ] 8. Implementar componente de conteúdo do álbum
  - [x] 8.1 Criar componente AlbumContent.vue
    - Implementar layout com duas seções (upload e galeria)
    - Integrar UploadArea na seção superior
    - Integrar MediaGallery na seção inferior
    - Implementar handlers para eventos de upload e exclusão
    - Aplicar estilos com largura flexível
    - _Requisitos: 2.3, 4.1, 6.4_
  
  - [ ]* 8.2 Escrever testes unitários para AlbumContent
    - Testar renderização de UploadArea e MediaGallery
    - Testar propagação de eventos
    - _Requisitos: 2.3_

- [ ] 9. Implementar estados vazios
  - [x] 9.1 Criar componente EmptyState.vue
    - Implementar template com ícone, título, mensagem e ação opcional
    - Implementar lógica para diferentes tipos (no-albums, no-media)
    - Aplicar estilos centralizados e amigáveis
    - _Requisitos: 8.1, 8.2, 8.3_
  
  - [ ]* 9.2 Escrever teste de propriedade para ações em estados vazios
    - **Propriedade 18: Ações em estados vazios**
    - **Valida: Requisitos 8.3**
  
  - [ ]* 9.3 Escrever testes unitários para EmptyState
    - Testar renderização para tipo no-albums
    - Testar renderização para tipo no-media
    - Testar presença de botão de ação
    - _Requisitos: 8.1, 8.2, 8.3_

- [x] 10. Checkpoint - Validar galeria e exclusão
  - Garantir que todos os testes passam
  - Verificar fluxo completo de upload e exclusão
  - Perguntar ao usuário se há dúvidas ou ajustes necessários

- [ ] 11. Implementar componente principal MediaScreen
  - [x] 11.1 Criar página MediaScreen.vue
    - Implementar layout de duas colunas
    - Integrar AlbumList na coluna esquerda
    - Integrar AlbumContent ou EmptyState na coluna direita
    - Implementar lógica de seleção de álbum
    - Implementar handler para criação de álbum
    - Integrar todos os composables (useAlbums, useMediaUpload, useMediaGallery)
    - _Requisitos: 1.1, 2.1, 2.2, 2.3, 3.3, 3.4, 3.6_
  
  - [ ]* 11.2 Escrever teste de propriedade para persistência de layout
    - **Propriedade 1: Persistência de elementos de layout**
    - **Valida: Requisitos 1.4**
  
  - [ ]* 11.3 Escrever teste de propriedade para ausência de scroll horizontal
    - **Propriedade 2: Ausência de scroll horizontal**
    - **Valida: Requisitos 2.4**
  
  - [ ]* 11.4 Escrever testes unitários para MediaScreen
    - Testar renderização com álbuns
    - Testar estado vazio sem álbuns
    - Testar seleção de álbum
    - Testar criação de álbum
    - _Requisitos: 1.1, 2.1, 3.3, 3.6, 8.1_

- [ ] 12. Implementar feedback visual e tratamento de erros
  - [x] 12.1 Criar sistema de notificações
    - Criar composable useNotifications para gerenciar notificações
    - Criar componente NotificationToast.vue para exibir mensagens
    - Implementar tipos de notificação (error, warning, info, success)
    - Implementar auto-dismiss com timeout configurável
    - _Requisitos: 9.2, 9.3, 9.4_
  
  - [x] 12.2 Integrar notificações nos componentes
    - Adicionar notificações de sucesso após upload completo
    - Adicionar notificações de erro em falhas de upload
    - Adicionar notificações de sucesso após exclusão
    - Adicionar notificações de erro em falhas de exclusão
    - _Requisitos: 5.3, 5.4, 9.3, 9.4_
  
  - [ ]* 12.3 Escrever teste de propriedade para indicadores de carregamento
    - **Propriedade 19: Indicador de carregamento para ações assíncronas**
    - **Valida: Requisitos 9.2**
  
  - [ ]* 12.4 Escrever teste de propriedade para feedback de sucesso
    - **Propriedade 20: Feedback de sucesso**
    - **Valida: Requisitos 9.3**
  
  - [ ]* 12.5 Escrever teste de propriedade para mensagens de erro
    - **Propriedade 21: Mensagens de erro acionáveis**
    - **Valida: Requisitos 9.4**

- [ ] 13. Implementar estilos e responsividade
  - [x] 13.1 Criar estilos globais com Tailwind CSS
    - Definir classes utilitárias customizadas para o tema
    - Implementar variáveis CSS para cores e espaçamentos
    - Configurar breakpoints responsivos
    - _Requisitos: 2.4, 6.5_
  
  - [x] 13.2 Aplicar estilos responsivos nos componentes
    - Implementar layout de duas colunas responsivo (stack em mobile)
    - Implementar grade de galeria responsiva com CSS Grid
    - Implementar estados hover, active e focus em elementos interativos
    - Garantir contraste adequado para acessibilidade (WCAG AA)
    - _Requisitos: 2.4, 6.5, 9.1_
  
  - [ ]* 13.3 Escrever testes de acessibilidade
    - Testar labels de botões
    - Testar atributos alt de imagens
    - Testar navegação por teclado
    - Testar contraste de cores
    - _Requisitos: 9.1_

- [ ] 14. Integrar com backend Laravel
  - [x] 14.1 Configurar rotas Inertia.js
    - Criar rota GET /midias para página principal
    - Criar rota POST /albums para criação de álbum
    - Criar rota POST /media/upload para upload de mídia
    - Criar rota DELETE /media/{id} para exclusão de mídia
    - _Requisitos: 10.1, 10.2_
  
  - [x] 14.2 Implementar controllers Laravel
    - Criar MediaScreenController com método index
    - Implementar AlbumController com método store
    - Implementar MediaController com métodos upload e destroy
    - Integrar com services existentes (AlbumService, MediaService)
    - _Requisitos: 10.2_
  
  - [x] 14.3 Configurar processamento assíncrono de uploads
    - Criar job ProcessMediaUpload para processamento em background
    - Configurar fila para jobs de upload
    - Implementar geração de thumbnails no job
    - _Requisitos: 10.3_
  
  - [ ]* 14.4 Implementar eventos em tempo real (opcional)
    - Configurar Laravel Echo para WebSockets
    - Criar evento MediaUploadProgress
    - Integrar listener no frontend para atualizar progresso
    - _Requisitos: 10.4_

- [ ] 15. Checkpoint final - Testes de integração
  - [ ]* 15.1 Escrever teste de integração para fluxo completo de upload
    - Testar seleção de álbum → upload → visualização na galeria
    - Testar atualização de contagem de mídias
    - _Requisitos: 3.4, 4.3, 5.3, 6.1, 7.5_
  
  - [ ]* 15.2 Escrever teste de integração para fluxo de exclusão
    - Testar seleção de mídia → confirmação → exclusão → atualização
    - _Requisitos: 7.2, 7.3, 7.5_
  
  - [x] 15.3 Executar todos os testes e verificar cobertura
    - Executar npm run test:coverage
    - Verificar cobertura mínima de 90% em componentes
    - Verificar cobertura de 95% em composables
    - Verificar cobertura de 100% em validações
  
  - [x] 15.4 Validação final com usuário
    - Garantir que todos os testes passam
    - Demonstrar funcionalidades principais
    - Perguntar ao usuário se há ajustes finais necessários

## Notas

- Tarefas marcadas com `*` são opcionais e podem ser puladas para um MVP mais rápido
- Cada tarefa referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Testes de propriedade validam propriedades universais de corretude
- Testes unitários validam exemplos específicos e edge cases
- A implementação segue uma abordagem incremental: tipos → lógica → componentes → integração
