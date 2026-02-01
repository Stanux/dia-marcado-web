# Requirements Document

## Introduction

Este documento especifica os requisitos para o sistema de gestão de usuários e permissões do SaaS de Casamentos. O sistema define como usuários são criados, seus tipos (Noivos, Organizadores, Convidados, Administradores), e como as permissões são gerenciadas dentro do contexto de cada casamento.

## Glossary

- **Noivos**: Usuários que criam uma conta pelo formulário público. Têm permissão de criar casamentos e gerenciar todos os aspectos do seu casamento.
- **Organizador**: Usuário criado pelos Noivos dentro de um casamento. Possui permissões definidas pelos Noivos para módulos específicos.
- **Convidado**: Usuário criado pelos Noivos ou Organizadores. Utiliza o APP para confirmar presença e participar de funcionalidades de convidados.
- **Administrador**: Usuário com acesso total à plataforma. Pode gerenciar qualquer casamento de qualquer usuário.
- **Módulo**: Funcionalidade específica do sistema que pode ter permissão concedida (Criação de Sites, Gestão de Tarefas, Convidados, Financeiro, Relatórios, APP, Gestão de Usuários).
- **Wedding_Context**: Contexto do casamento ativo para o usuário autenticado.
- **Permission_Set**: Conjunto de permissões de módulos atribuídas a um Organizador.

## Requirements

### Requirement 1: Registro Público de Noivos

**User Story:** Como visitante, quero criar uma conta pelo formulário de registro, para que eu possa criar e gerenciar meu casamento.

#### Acceptance Criteria

1. WHEN um visitante submete o formulário de registro público, THE System SHALL criar um usuário com role "couple"
2. WHEN um usuário do tipo Noivos é criado, THE System SHALL permitir que ele crie um novo casamento
3. WHEN um Noivos cria um casamento, THE System SHALL vincular automaticamente o usuário ao casamento com role "couple" na tabela pivot
4. THE System SHALL exigir nome, email e senha no formulário de registro público
5. IF um email já existir no sistema, THEN THE System SHALL retornar erro de validação informando que o email já está em uso

### Requirement 2: Gestão de Organizadores pelos Noivos

**User Story:** Como Noivos, quero criar usuários do tipo Organizador vinculados ao meu casamento, para que eles me ajudem a gerenciar o evento.

#### Acceptance Criteria

1. WHEN um Noivos acessa o módulo de Gestão de Usuários, THE System SHALL exibir opção para criar Organizador
2. WHEN um Noivos cria um Organizador, THE System SHALL criar um usuário com role "organizer" vinculado ao casamento
3. WHEN um Noivos cria um Organizador, THE System SHALL permitir definir quais módulos o Organizador pode acessar
4. THE System SHALL suportar permissões nos módulos: Criação de Sites, Gestão de Tarefas, Convidados, Financeiro, Relatórios, APP e Gestão de Usuários
5. WHEN um Organizador tem permissão no módulo Gestão de Usuários, THE System SHALL permitir que ele crie apenas Convidados
6. IF um Noivos remover um Organizador, THEN THE System SHALL desvincular o usuário do casamento mantendo o registro do usuário

### Requirement 3: Gestão de Convidados

**User Story:** Como Noivos ou Organizador com permissão, quero criar usuários do tipo Convidado, para que eles possam usar o APP e confirmar presença.

#### Acceptance Criteria

1. WHEN um Noivos ou Organizador com permissão acessa o módulo de Convidados, THE System SHALL exibir opção para criar Convidado
2. WHEN um Convidado é criado, THE System SHALL criar um usuário com role "guest" vinculado ao casamento
3. THE System SHALL permitir criar Convidado com nome e email obrigatórios
4. THE System SHALL permitir criar Convidado sem senha inicial, gerando um convite por email
5. WHEN um Convidado acessa o APP, THE System SHALL permitir apenas funcionalidades de convidado: notificações, gamification e confirmação de presença
6. IF um Convidado tentar acessar módulos administrativos, THEN THE System SHALL retornar erro 403 Forbidden

### Requirement 4: Módulo de Gestão de Permissões

**User Story:** Como Noivos, quero um módulo para gerenciar as permissões dos Organizadores, para que eu possa controlar o que cada um pode fazer.

#### Acceptance Criteria

1. THE System SHALL fornecer um módulo de Gestão de Permissões acessível apenas por Noivos e Administradores
2. WHEN um Noivos acessa o módulo de Gestão de Permissões, THE System SHALL listar todos os Organizadores do casamento
3. WHEN um Noivos edita as permissões de um Organizador, THE System SHALL permitir ativar/desativar acesso a cada módulo individualmente
4. WHEN as permissões de um Organizador são alteradas, THE System SHALL aplicar as mudanças imediatamente
5. THE System SHALL exibir visualmente quais módulos cada Organizador pode acessar
6. IF um Organizador perder permissão em um módulo enquanto estiver usando, THEN THE System SHALL redirecionar para a página inicial na próxima requisição

### Requirement 5: Administradores do Sistema

**User Story:** Como Administrador, quero gerenciar qualquer casamento da plataforma, para que eu possa dar suporte aos usuários.

#### Acceptance Criteria

1. WHEN um Administrador acessa a plataforma, THE System SHALL permitir visualizar lista de todos os casamentos
2. WHEN um Administrador seleciona um casamento, THE System SHALL permitir acesso total a todos os módulos daquele casamento
3. THE System SHALL permitir que Administradores criem outros Administradores
4. THE System SHALL registrar em log todas as ações de Administradores em casamentos de terceiros
5. IF um Administrador modificar dados de um casamento, THEN THE System SHALL registrar o ID do administrador e timestamp da ação

### Requirement 6: Hierarquia de Criação de Usuários

**User Story:** Como sistema, quero garantir que a hierarquia de criação de usuários seja respeitada, para manter a segurança e organização.

#### Acceptance Criteria

1. THE System SHALL permitir que apenas Administradores criem outros Administradores
2. THE System SHALL permitir que Noivos criem Organizadores e Convidados no seu casamento
3. THE System SHALL permitir que Organizadores com permissão criem apenas Convidados no casamento
4. THE System SHALL impedir que Convidados criem qualquer tipo de usuário
5. IF um usuário tentar criar um tipo de usuário não permitido pela hierarquia, THEN THE System SHALL retornar erro 403 Forbidden
6. WHEN um usuário é criado, THE System SHALL registrar qual usuário o criou (created_by)

### Requirement 7: Listagem e Filtros de Usuários

**User Story:** Como Noivos ou Organizador com permissão, quero visualizar e filtrar os usuários do meu casamento, para gerenciá-los facilmente.

#### Acceptance Criteria

1. WHEN um usuário acessa o módulo de Gestão de Usuários, THE System SHALL listar todos os usuários vinculados ao casamento
2. THE System SHALL permitir filtrar usuários por tipo (Organizador, Convidado)
3. THE System SHALL permitir buscar usuários por nome ou email
4. THE System SHALL exibir para cada usuário: nome, email, tipo, status e data de criação
5. WHEN um Organizador acessa a listagem, THE System SHALL exibir apenas Convidados (não outros Organizadores)
6. THE System SHALL permitir ordenar a listagem por nome, tipo ou data de criação
