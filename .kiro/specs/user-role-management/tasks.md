# Implementation Plan: User Role Management

## Overview

Este plano implementa o sistema de gestão de usuários e permissões do SaaS de Casamentos. A implementação segue uma abordagem incremental, começando pela estrutura de banco de dados, seguindo para os serviços de negócio, e finalizando com a interface de usuário no FilamentPHP.

## Tasks

- [x] 1. Atualizar estrutura de banco de dados
  - [x] 1.1 Criar migration para adicionar campo created_by na tabela users
    - Adicionar coluna uuid created_by nullable
    - Criar foreign key para users.id com nullOnDelete
    - _Requirements: 6.6_
  - [x] 1.2 Criar migration para tabela admin_audit_logs
    - UUID como primary key
    - Foreign keys para admin_id e wedding_id
    - Campos action, details (JSONB), performed_at
    - Índices em (admin_id, performed_at) e (wedding_id, performed_at)
    - _Requirements: 5.4, 5.5_
  - [x] 1.3 Executar migrations e verificar estrutura
    - _Requirements: 5.4, 6.6_

- [x] 2. Implementar serviço de registro público
  - [x] 2.1 Criar UserRegistrationService
    - Método registerCouple() que sempre cria usuário com role "couple"
    - Validação de campos obrigatórios (nome, email, senha)
    - Verificação de email único
    - _Requirements: 1.1, 1.4, 1.5_
  - [x] 2.2 Escrever property test para registro sempre criar Noivos
    - **Property 1: Registro Público Sempre Cria Noivos**
    - **Validates: Requirements 1.1**
  - [x] 2.3 Escrever property test para validação de campos obrigatórios
    - **Property 3: Validação de Campos Obrigatórios no Registro**
    - **Validates: Requirements 1.4**
  - [x] 2.4 Escrever property test para email único
    - **Property 4: Email Único no Sistema**
    - **Validates: Requirements 1.5**

- [x] 3. Implementar vinculação automática de Noivos ao casamento
  - [x] 3.1 Atualizar WeddingService para vincular criador automaticamente
    - Ao criar casamento, vincular usuário com role "couple" na pivot
    - _Requirements: 1.2, 1.3_
  - [x] 3.2 Escrever property test para vinculação automática
    - **Property 2: Noivos Vinculado ao Casamento Criado**
    - **Validates: Requirements 1.2, 1.3**

- [x] 4. Checkpoint - Verificar registro e vinculação
  - Testar fluxo completo de registro → criação de casamento
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implementar serviço de gestão de usuários
  - [x] 5.1 Criar UserManagementService
    - Método createOrganizer() com validação de hierarquia
    - Método createGuest() com validação de hierarquia
    - Método createAdmin() apenas para Admins
    - Método removeFromWedding() para desvincular usuário
    - _Requirements: 2.2, 2.3, 3.2, 5.3, 6.1, 6.2, 6.4_
  - [x] 5.2 Escrever property test para criação de Organizador
    - **Property 5: Criação de Organizador com Permissões**
    - **Validates: Requirements 2.2, 2.3**
  - [x] 5.3 Escrever property test para remoção preservar usuário
    - **Property 6: Remoção de Organizador Preserva Usuário**
    - **Validates: Requirements 2.6**
  - [x] 5.4 Escrever property test para criação de Convidado
    - **Property 7: Criação de Convidado com Role Guest**
    - **Validates: Requirements 3.2**
  - [x] 5.5 Escrever property test para validação de Convidado
    - **Property 8: Validação de Campos Obrigatórios para Convidado**
    - **Validates: Requirements 3.3**
  - [x] 5.6 Escrever property test para registro de created_by
    - **Property 13: Registro de Created By**
    - **Validates: Requirements 6.6**

- [x] 6. Implementar serviço de gestão de permissões
  - [x] 6.1 Criar PermissionManagementService
    - Constante AVAILABLE_MODULES com todos os módulos
    - Método updateOrganizerPermissions()
    - Método getOrganizersWithPermissions()
    - _Requirements: 2.4, 4.2, 4.3, 4.4_
  - [x] 6.2 Atualizar PermissionService existente
    - Integrar com novos módulos (incluindo 'users')
    - Garantir que alterações são aplicadas imediatamente
    - _Requirements: 4.4_
  - [x] 6.3 Escrever property test para hierarquia de permissões
    - **Property 9: Hierarquia de Permissões por Role**
    - **Validates: Requirements 3.5, 4.1, 5.2, 6.1, 6.2, 6.4**
  - [x] 6.4 Escrever property test para alteração imediata
    - **Property 10: Alteração de Permissões é Imediata**
    - **Validates: Requirements 4.3, 4.4**

- [x] 7. Checkpoint - Verificar serviços de usuários e permissões
  - Testar criação de Organizadores e Convidados
  - Testar alteração de permissões
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Implementar serviço de auditoria de Admin
  - [x] 8.1 Criar Model AdminAuditLog
    - Relacionamentos com User (admin) e Wedding
    - Cast details para array
    - _Requirements: 5.4_
  - [x] 8.2 Criar AdminAuditService
    - Método logAction() que registra apenas ações em casamentos de terceiros
    - _Requirements: 5.4, 5.5_
  - [x] 8.3 Escrever property test para log de ações de Admin
    - **Property 12: Log de Ações de Admin em Casamentos de Terceiros**
    - **Validates: Requirements 5.4, 5.5**

- [x] 9. Implementar serviço de listagem de usuários
  - [x] 9.1 Criar UserListingService
    - Método listUsers() com filtros e ordenação
    - Organizador só vê Convidados
    - Filtro por tipo, busca por nome/email
    - Ordenação por nome, tipo ou data
    - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6_
  - [x] 9.2 Escrever property test para listagem completa
    - **Property 14: Listagem de Usuários do Casamento**
    - **Validates: Requirements 7.1**
  - [x] 9.3 Escrever property test para filtro por tipo
    - **Property 15: Filtro por Tipo Funciona Corretamente**
    - **Validates: Requirements 7.2, 7.5**
  - [x] 9.4 Escrever property test para busca
    - **Property 16: Busca por Nome ou Email**
    - **Validates: Requirements 7.3**
  - [x] 9.5 Escrever property test para ordenação
    - **Property 17: Ordenação Funciona Corretamente**
    - **Validates: Requirements 7.6**

- [x] 10. Implementar acesso de Admin a todos os casamentos
  - [x] 10.1 Atualizar WeddingScope para Admin ver todos
    - Garantir que Admin não é filtrado pelo scope
    - _Requirements: 5.1, 5.2_
  - [x] 10.2 Escrever property test para Admin ver todos casamentos
    - **Property 11: Admin Vê Todos os Casamentos**
    - **Validates: Requirements 5.1**

- [x] 11. Checkpoint - Verificar serviços de auditoria e listagem
  - Testar log de ações de Admin
  - Testar listagem com filtros e ordenação
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Implementar interface FilamentPHP para gestão de usuários
  - [x] 12.1 Criar Resource UserResource no FilamentPHP
    - Listagem com filtros por tipo
    - Busca por nome e email
    - Ordenação configurável
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.6_
  - [x] 12.2 Criar formulário de criação de Organizador
    - Campos nome, email, senha
    - Seleção de permissões por módulo (checkboxes)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [x] 12.3 Criar formulário de criação de Convidado
    - Campos nome, email
    - Opção de enviar convite por email
    - _Requirements: 3.1, 3.3, 3.4_
  - [x] 12.4 Implementar página de gestão de permissões
    - Listagem de Organizadores com suas permissões
    - Edição inline de permissões por módulo
    - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [x] 13. Implementar controle de acesso na interface
  - [x] 13.1 Configurar middleware de permissões no FilamentPHP
    - Verificar permissão antes de exibir cada módulo
    - Redirecionar para home se perder permissão
    - _Requirements: 4.6, 2.7_
  - [x] 13.2 Filtrar opções de criação por hierarquia
    - Noivos vê opção de criar Organizador e Convidado
    - Organizador com permissão vê apenas criar Convidado
    - _Requirements: 2.5, 6.2, 6.3_

- [x] 14. Atualizar formulário de registro público
  - [x] 14.1 Garantir que registro sempre cria role "couple"
    - Criada página de registro customizada em `app/Filament/Pages/Auth/Register.php`
    - Usa `UserRegistrationService::registerCouple()` para garantir role "couple"
    - Configurado `AdminPanelProvider` para usar a página customizada
    - _Requirements: 1.1_

- [x] 15. Checkpoint Final - Validação completa
  - [x] Executar todos os testes - 42 testes passaram com 7314 assertions
  - Fluxo completo implementado: registro → criar casamento → criar organizador → criar convidado
  - Permissões testadas em todos os cenários

## Notes

- Todas as tasks são obrigatórias para uma implementação robusta
- Cada task referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Property tests validam propriedades universais de corretude
- A implementação reutiliza o PermissionService existente do projeto base
