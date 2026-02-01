# Implementation Plan: Wedding SaaS Foundation

## Overview

Este plano implementa a fundação do SaaS de Casamentos com Single Database, incluindo infraestrutura Docker, sistema de isolamento de dados por wedding_id, e sistema de perfis com permissões granulares. A implementação segue uma abordagem incremental, validando cada componente antes de avançar.

## Tasks

- [x] 1. Configurar infraestrutura Docker
  - [x] 1.1 Criar docker-compose.yml com serviços nginx, php, postgres, redis e mailpit
    - Configurar volumes para persistência de dados
    - Configurar network bridge para comunicação entre containers
    - _Requirements: 3.1, 3.4, 3.5, 3.6_
  - [x] 1.2 Criar Dockerfile para PHP 8.3-FPM
    - Instalar extensões: pdo_pgsql, zip, opcache
    - Configurar Composer
    - _Requirements: 3.1_
  - [x] 1.3 Criar configuração nginx para Laravel
    - Rotear requisições para php-fpm na porta 9000
    - Configurar assets estáticos
    - _Requirements: 3.4_

- [x] 2. Configurar projeto Laravel base
  - [x] 2.1 Criar projeto Laravel 12 com configurações iniciais
    - Configurar .env para PostgreSQL e Redis
    - Configurar drivers de log (daily) e queue (redis)
    - _Requirements: 3.2, 3.3_
  - [x] 2.2 Instalar e configurar dependências
    - Laravel Sanctum para autenticação API
    - Inertia.js + Vue.js 3
    - Tailwind CSS
    - FilamentPHP
    - _Requirements: 4.1, 8.1, 8.2, 8.3, 7.1_

- [x] 3. Implementar estrutura de banco de dados
  - [x] 3.1 Criar migration para tabela users com campo role
    - UUID como primary key
    - Enum role: admin, couple, organizer, guest
    - Campo current_wedding_id nullable
    - _Requirements: 5.5_
  - [x] 3.2 Criar migration para tabela weddings com JSONB settings
    - UUID como primary key
    - Campo settings como JSONB
    - _Requirements: 5.4_
  - [x] 3.3 Criar migration para tabela pivot wedding_user
    - Foreign keys para users e weddings
    - Enum role: couple, organizer, guest
    - Campo permissions como JSONB
    - Índice em wedding_id
    - _Requirements: 5.1, 5.2, 5.6_
  - [x] 3.4 Escrever property test para JSONB settings round-trip
    - **Property 6: JSONB Settings Round-Trip**
    - **Validates: Requirements 5.4**

- [x] 4. Checkpoint - Verificar infraestrutura e banco
  - Executar migrations
  - Verificar conexão com PostgreSQL e Redis
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implementar sistema de isolamento por Wedding
  - [x] 5.1 Criar WeddingScope (Global Scope)
    - Filtrar queries pelo wedding_id do usuário autenticado
    - Permitir Admin ver todos os registros
    - _Requirements: 1.1, 1.3_
  - [x] 5.2 Criar WeddingScopedModel (Base Model)
    - Aplicar WeddingScope automaticamente
    - Injetar wedding_id na criação de registros
    - _Requirements: 1.1, 1.2_
  - [x] 5.3 Escrever property test para filtro automático por wedding_id
    - **Property 1: Filtro Automático por Wedding ID**
    - **Validates: Requirements 1.1, 1.3**
  - [x] 5.4 Escrever property test para injeção automática de wedding_id
    - **Property 2: Injeção Automática de Wedding ID**
    - **Validates: Requirements 1.2, 9.2**

- [x] 6. Implementar sistema de permissões
  - [x] 6.1 Criar PermissionService
    - Definir constantes de módulos e roles
    - Implementar método canAccess(user, module, wedding)
    - _Requirements: 2.1, 2.5_
  - [x] 6.2 Implementar lógica de permissões por perfil
    - Admin: acesso total
    - Noivo: acesso a casamentos que participa
    - Organizador: acesso a módulos configurados
    - Convidado: acesso apenas ao módulo APP
    - _Requirements: 2.2, 2.3, 2.6_
  - [x] 6.3 Escrever property test para permissões por perfil
    - **Property 4: Permissões por Perfil**
    - **Validates: Requirements 2.2, 2.3, 2.6**

- [x] 7. Checkpoint - Verificar isolamento e permissões
  - Testar WeddingScope com diferentes perfis
  - Testar PermissionService com diferentes cenários
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Implementar autenticação API com Sanctum
  - [x] 8.1 Criar middleware EnsureWeddingContext
    - Validar token Sanctum
    - Extrair wedding_id do header ou rota
    - Verificar acesso do usuário ao wedding
    - _Requirements: 4.2, 4.3, 4.4_
  - [x] 8.2 Configurar rotas API com middleware
    - Aplicar middleware de autenticação
    - Aplicar middleware de wedding context
    - _Requirements: 4.5_
  - [x] 8.3 Escrever property test para autorização e erros de acesso
    - **Property 3: Autorização e Erros de Acesso**
    - **Validates: Requirements 1.4, 2.7, 4.3, 4.4**
  - [x] 8.4 Escrever property test para token com contexto
    - **Property 5: Token com Contexto de Wedding**
    - **Validates: Requirements 4.2**

- [x] 9. Implementar Models base
  - [x] 9.1 Criar Model User com relacionamentos
    - Relacionamento weddings (belongsToMany)
    - Métodos isAdmin(), isCoupleIn(wedding), etc.
    - _Requirements: 2.1_
  - [x] 9.2 Criar Model Wedding com relacionamentos
    - Relacionamento users (belongsToMany)
    - Cast settings para array
    - _Requirements: 5.4_
  - [x] 9.3 Criar Factories para User e Wedding
    - UserFactory com estados para cada role
    - WeddingFactory com settings aleatórios
    - _Requirements: Testing_

- [ ] 10. Configurar Inertia.js com dados do usuário
  - [ ] 10.1 Criar middleware HandleInertiaRequests
    - Passar wedding_id, role e permissions para o frontend
    - _Requirements: 8.4_
  - [ ] 10.2 Escrever property test para dados do usuário no frontend
    - **Property 7: Dados do Usuário no Frontend**
    - **Validates: Requirements 8.4**

- [x] 11. Configurar FilamentPHP com isolamento
  - [x] 11.1 Criar PanelProvider com autenticação
    - Configurar middleware de autenticação
    - Configurar middleware de permissões
    - _Requirements: 7.1, 7.3_
  - [x] 11.2 Criar Resource base com WeddingScope
    - Estender WeddingScopedModel nos resources
    - Filtrar dados por wedding_id
    - _Requirements: 7.2, 7.4, 7.5_

- [x] 12. Checkpoint Final - Validação completa
  - Executar todos os testes
  - Verificar isolamento de dados end-to-end
  - Verificar permissões em todos os perfis
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Todas as tasks são obrigatórias para uma implementação robusta
- Cada task referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Property tests validam propriedades universais de corretude
- Unit tests validam exemplos específicos e edge cases
