# Plano de Implementação: Módulo de Gerenciamento de Mídia

## Visão Geral

Este plano implementa o módulo de gerenciamento de mídia para a plataforma de casamentos, incluindo upload assíncrono de múltiplos arquivos, organização em álbuns por tipo, controle de cotas por plano e integração com o construtor de sites.

## Tarefas

- [x] 1. Configurar estrutura de banco de dados e models
  - [x] 1.1 Criar migration para tabela album_types com os três tipos fixos
    - Criar migration com campos: id, slug, name, description
    - Inserir registros: pre_casamento, pos_casamento, uso_site
    - _Requisitos: 2.1_
  
  - [x] 1.2 Criar migration para tabela albums
    - Campos: id (uuid), wedding_id, album_type_id, name, description, cover_media_id, timestamps
    - Foreign keys para weddings, album_types, site_media
    - _Requisitos: 2.2, 2.3_
  
  - [x] 1.3 Criar migration para adicionar campos ao site_media
    - Adicionar: album_id, status, batch_id, error_message
    - Status enum: pending, processing, completed, failed
    - _Requisitos: 1.1, 1.4_
  
  - [x] 1.4 Criar migration para tabela plan_limits
    - Campos: id, plan_slug, max_files, max_storage_bytes
    - Inserir limites para basic e premium  
    - _Requisitos: 4.1, 4.2, 4.5_
  
  - [x] 1.5 Criar migration para tabela upload_batches
    - Campos: id (uuid), wedding_id, album_id, total_files, completed_files, failed_files, status, timestamps
    - _Requisitos: 1.1, 1.5_
  
  - [x] 1.6 Criar models AlbumType, Album, PlanLimit, UploadBatch
    - Definir relacionamentos, fillable, casts
    - Atualizar SiteMedia com novos relacionamentos
    - _Requisitos: 2.2, 2.3, 4.1_

- [x] 2. Implementar DTOs e Value Objects
  - [x] 2.1 Criar QuotaUsage DTO
    - Propriedades: currentFiles, maxFiles, currentStorageBytes, maxStorageBytes, filesPercentage, storagePercentage
    - Métodos: isAtLimit(), isNearLimit(threshold)
    - _Requisitos: 5.1, 5.3, 5.5_
  
  - [x] 2.2 Criar QuotaCheckResult DTO
    - Propriedades: canUpload, reason, upgradeMessage
    - _Requisitos: 4.3, 4.4, 5.4_
  
  - [x] 2.3 Criar BatchStatus DTO
    - Propriedades: batchId, total, completed, failed, pending, errors
    - Métodos: isComplete(), getProgressPercentage()
    - _Requisitos: 1.5_
  
  - [x] 2.4 Criar UploadResult DTO
    - Propriedades: mediaId, success, media, error
    - _Requisitos: 1.4, 1.5_
  
  - [x] 2.5 Escrever teste de propriedade para QuotaUsage
    - **Propriedade 12: Alerta de Cota em 80%**
    - **Valida: Requisitos 5.3**

- [x] 3. Implementar QuotaTrackingService
  - [x] 3.1 Criar interface QuotaTrackingServiceInterface
    - Métodos: getUsage(), canUpload(), getPlanLimits(), getUsagePercentage()
    - _Requisitos: 5.1_
  
  - [x] 3.2 Implementar QuotaTrackingService
    - Calcular uso atual de arquivos e storage por wedding
    - Verificar limites do plano antes de upload
    - Cachear resultados para performance
    - _Requisitos: 5.1, 5.5, 5.6_
  
  - [x] 3.3 Escrever teste de propriedade para cálculo de cota
    - **Propriedade 11: Cálculo Correto de Uso de Cota**
    - **Valida: Requisitos 5.1, 5.5, 5.6**
  
  - [x] 3.4 Escrever teste de propriedade para verificação de cota
    - **Propriedade 9: Verificação de Cota Bloqueia Uploads**
    - **Valida: Requisitos 4.3, 4.4**
  
  - [x] 3.5 Escrever teste de propriedade para oferta de upgrade
    - **Propriedade 13: Oferta de Upgrade para Plano Básico em 100%**
    - **Valida: Requisitos 5.4**

- [x] 4. Checkpoint - Verificar estrutura base
  - Executar migrations e verificar estrutura do banco
  - Executar testes de propriedade implementados
  - Perguntar ao usuário se há dúvidas

- [x] 5. Implementar AlbumManagementService
  - [x] 5.1 Criar interface AlbumManagementServiceInterface
    - Métodos: createAlbum(), updateAlbum(), deleteAlbum(), moveMedia(), getAlbumsByType()
    - _Requisitos: 2.2, 2.4, 2.5_
  
  - [x] 5.2 Implementar AlbumManagementService
    - Validar tipo de álbum na criação
    - Agrupar álbuns por tipo na listagem
    - Mover mídia entre álbuns sem duplicar arquivo
    - _Requisitos: 2.2, 2.4, 2.5, 2.6_
  
  - [x] 5.3 Escrever teste de propriedade para integridade referencial
    - **Propriedade 4: Integridade Referencial de Álbum e Mídia**
    - **Valida: Requisitos 2.2, 2.3**
  
  - [x] 5.4 Escrever teste de propriedade para agrupamento
    - **Propriedade 5: Agrupamento de Álbuns por Tipo**
    - **Valida: Requisitos 2.4**
  
  - [x] 5.5 Escrever teste de propriedade para mover mídia
    - **Propriedade 6: Mover Mídia Preserva Arquivo Único**
    - **Valida: Requisitos 2.5, 8.4**

- [x] 6. Estender MediaUploadService para validações
  - [x] 6.1 Adicionar validação de dimensões máximas de imagem
    - Ler configuração de SystemConfig
    - Redimensionar automaticamente se exceder
    - _Requisitos: 3.1, 3.4_
  
  - [x] 6.2 Adicionar validação de tamanho por tipo de arquivo
    - Limites diferentes para imagem e vídeo
    - Rejeitar com mensagem explicativa
    - _Requisitos: 3.2, 3.3, 3.5_
  
  - [x] 6.3 Escrever teste de propriedade para redimensionamento
    - **Propriedade 7: Redimensionamento Automático de Imagens Grandes**
    - **Valida: Requisitos 3.4**
  
  - [x] 6.4 Escrever teste de propriedade para rejeição por tamanho
    - **Propriedade 8: Rejeição de Arquivos Acima do Limite**
    - **Valida: Requisitos 3.5**
  
  - [x] 6.5 Escrever teste de propriedade para validação de arquivo
    - **Propriedade 16: Validação de Arquivo Rejeita Inválidos**
    - **Valida: Requisitos 7.1, 7.2, 7.3, 7.4**
  
  - [x] 6.6 Escrever teste de propriedade para nomes UUID
    - **Propriedade 18: Nomes UUID para Arquivos**
    - **Valida: Requisitos 7.6**
  
  - [x] 6.7 Escrever teste de propriedade para geração de variantes
    - **Propriedade 17: Geração de Variantes para Imagens**
    - **Valida: Requisitos 7.5**

- [x] 7. Implementar BatchUploadService e Jobs
  - [x] 7.1 Criar interface BatchUploadServiceInterface
    - Métodos: createBatch(), processFile(), cancelBatch(), getBatchStatus()
    - _Requisitos: 1.1, 1.6_
  
  - [x] 7.2 Implementar BatchUploadService
    - Criar registros pendentes para cada arquivo do batch
    - Gerenciar status do batch
    - _Requisitos: 1.1, 1.5_
  
  - [x] 7.3 Criar job ProcessMediaUpload
    - Processar arquivo da fila
    - Validar, armazenar, otimizar
    - Atualizar status para completed ou failed
    - _Requisitos: 1.2, 1.4_
  
  - [x] 7.4 Criar job OptimizeMediaImage
    - Gerar variantes (thumbnail, webp, 1x/2x)
    - Executar após upload bem-sucedido
    - _Requisitos: 7.5_
  
  - [x] 7.5 Escrever teste de propriedade para criação de batch
    - **Propriedade 1: Criação de Batch Cria Entradas Corretas**
    - **Valida: Requisitos 1.1**
  
  - [x] 7.6 Escrever teste de propriedade para falhas isoladas
    - **Propriedade 2: Falhas Isoladas Não Afetam Outros Uploads**
    - **Valida: Requisitos 1.4**
  
  - [x] 7.7 Escrever teste de propriedade para resumo de batch
    - **Propriedade 3: Resumo de Batch Contém Contagens Corretas**
    - **Valida: Requisitos 1.5**

- [x] 8. Checkpoint - Verificar serviços backend
  - Executar todos os testes de propriedade
  - Verificar integração entre serviços
  - Perguntar ao usuário se há dúvidas

- [x] 9. Implementar Controllers da API
  - [x] 9.1 Criar MediaController com endpoints de batch
    - POST /media/batch - criar batch
    - POST /media/{id}/upload - upload de arquivo individual
    - DELETE /media/{id}/cancel - cancelar upload
    - _Requisitos: 1.1, 1.6_
  
  - [x] 9.2 Criar endpoints de gerenciamento de mídia
    - GET /media - listar com filtros
    - GET /media/{id} - detalhes
    - DELETE /media/{id} - excluir
    - POST /media/batch-delete - excluir em lote
    - POST /media/{id}/move - mover para outro álbum
    - _Requisitos: 8.1, 8.3, 8.4, 8.5, 8.6_
  
  - [x] 9.3 Criar AlbumController
    - CRUD completo de álbuns
    - Listagem agrupada por tipo
    - _Requisitos: 2.2, 2.4, 2.6_
  
  - [x] 9.4 Criar QuotaController
    - GET /quota - uso atual
    - POST /quota/check - verificar se pode fazer upload
    - _Requisitos: 5.1, 4.3, 4.4_
  
  - [x] 9.5 Escrever teste de propriedade para exclusão
    - **Propriedade 19: Exclusão Remove Arquivo e Variantes**
    - **Valida: Requisitos 8.3**
  
  - [x] 9.6 Escrever teste de propriedade para operações em lote
    - **Propriedade 20: Operações em Lote Afetam Todos os Itens**
    - **Valida: Requisitos 8.5**
  
  - [x] 9.7 Escrever teste de propriedade para busca
    - **Propriedade 21: Busca Filtra Corretamente**
    - **Valida: Requisitos 8.6**
  
  - [x] 9.8 Escrever teste de propriedade para filtragem
    - **Propriedade 14: Filtragem de Mídia por Álbum e Tipo**
    - **Valida: Requisitos 6.2, 8.6**

- [x] 10. Implementar componentes Vue.js
  - [x] 10.1 Criar componente BatchUploader
    - Seleção de múltiplos arquivos
    - Barra de progresso individual por arquivo
    - Tratamento de erros por arquivo
    - _Requisitos: 1.1, 1.3, 1.4_
  
  - [x] 10.2 Criar componente MediaLibrary
    - Grid de thumbnails com informações
    - Filtros por álbum e tipo
    - Seleção única e múltipla
    - _Requisitos: 6.2, 8.1, 8.6_
  
  - [x] 10.3 Criar componente AlbumSelector
    - Dropdown com álbuns agrupados por tipo
    - Opção de criar novo álbum inline
    - _Requisitos: 2.4, 6.3, 6.4_
  
  - [x] 10.4 Criar componente QuotaWidget
    - Exibição de uso em percentual e absoluto
    - Alerta visual em 80%
    - Botão de upgrade em 100%
    - _Requisitos: 5.1, 5.3, 5.4, 5.5_

- [x] 11. Integrar com construtor de sites
  - [x] 11.1 Atualizar MediaUploader existente
    - Adicionar opção "Escolher da biblioteca"
    - Integrar com MediaLibrary
    - _Requisitos: 6.1_
  
  - [x] 11.2 Adicionar fluxo de categorização no upload
    - Modal para selecionar tipo e álbum
    - Permitir criar álbum se não existir
    - _Requisitos: 6.3, 6.4_
  
  - [x] 11.3 Retornar URL otimizada na seleção
    - Preferir variante webp quando disponível
    - Fallback para original
    - _Requisitos: 6.5_
  
  - [x] 11.4 Escrever teste de propriedade para URL otimizada
    - **Propriedade 15: URL Otimizada Retornada para Mídia**
    - **Valida: Requisitos 6.5**

- [x] 12. Implementar painel administrativo Filament
  - [x] 12.1 Criar resource para configurações de mídia
    - Campos para dimensões e tamanhos máximos
    - Usar SystemConfig para persistência
    - _Requisitos: 3.1, 3.2, 3.3_
  
  - [x] 12.2 Criar resource para limites de plano
    - CRUD de PlanLimit
    - Validação de valores
    - _Requisitos: 4.1, 4.2_

- [x] 13. Checkpoint final
  - Executar todos os testes (unitários e propriedade)
  - Verificar integração completa
  - Perguntar ao usuário se há dúvidas

## Notas

- Todas as tarefas são obrigatórias, incluindo testes de propriedade
- Cada tarefa referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Testes de propriedade validam propriedades universais de corretude
- Testes unitários validam exemplos específicos e casos de borda
