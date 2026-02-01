# Requirements Document

## Introduction

Este documento especifica os requisitos para a fundação do SaaS de Casamentos com arquitetura Single Database. O sistema permite que múltiplos casamentos compartilhem a mesma infraestrutura de banco de dados, com isolamento de dados garantido por Global Scopes no Laravel. A stack inclui Laravel 12, Vue.js 3 com Inertia.js, PostgreSQL, Redis e FilamentPHP para o painel administrativo.

## Glossary

- **Wedding**: Entidade principal que representa um casamento no sistema. Cada casamento possui um identificador único (UUID ou ID numérico).
- **WeddingScope**: Global Scope do Laravel que filtra automaticamente todas as queries pelo `wedding_id` do usuário autenticado.
- **WeddingScopedModel**: Classe base abstrata que todos os Models relacionados a um casamento devem estender.
- **Sanctum**: Pacote Laravel para autenticação de APIs via tokens.
- **FilamentPHP**: Framework de painel administrativo para Laravel.
- **Global_Scope**: Mecanismo do Laravel que aplica filtros automaticamente em todas as queries de um Model.
- **Docker_Stack**: Conjunto de containers Docker que compõem o ambiente de desenvolvimento.
- **JSONB**: Tipo de dado PostgreSQL para armazenamento de JSON com indexação.
- **Admin**: Perfil com permissão total na plataforma.
- **Noivo**: Perfil que pode gerenciar casamentos que criou ou participa como noivo/noiva.
- **Organizador**: Perfil com permissões específicas em módulos, concedidas por Noivos ou Admin.
- **Convidado**: Perfil que utiliza o APP e funcionalidades direcionadas a convidados.
- **Módulo**: Funcionalidade específica do sistema (Criação de Sites, Gestão de Tarefas, Convidados, Financeiro, Relatórios, APP).

## Requirements

### Requirement 1: Isolamento de Dados por Wedding

**User Story:** Como desenvolvedor, quero que todas as queries sejam automaticamente filtradas pelo wedding_id, para que não haja vazamento de dados entre casamentos.

#### Acceptance Criteria

1. THE WeddingScopedModel SHALL aplicar automaticamente o WeddingScope em todas as queries
2. WHEN um novo registro for criado em um Model que estende WeddingScopedModel, THE System SHALL injetar automaticamente o wedding_id do usuário autenticado
3. WHEN uma query for executada em um Model que estende WeddingScopedModel, THE System SHALL filtrar os resultados pelo wedding_id do usuário autenticado
4. IF uma tentativa de acesso a dados de outro wedding_id for detectada, THEN THE System SHALL retornar erro 403 Forbidden

### Requirement 2: Sistema de Perfis e Permissões

**User Story:** Como plataforma, quero controlar o acesso por perfis com permissões granulares, para que cada usuário tenha acesso apenas às funcionalidades permitidas.

#### Acceptance Criteria

1. THE System SHALL suportar os perfis: Admin, Noivo, Organizador e Convidado
2. WHEN um usuário Admin acessar a plataforma, THE System SHALL conceder permissão total a todos os recursos
3. WHEN um usuário Noivo acessar a plataforma, THE System SHALL permitir gerenciar apenas casamentos que ele criou ou participa como noivo/noiva
4. WHEN um Organizador for adicionado a um casamento, THE System SHALL permitir que Noivos ou Admin definam quais módulos ele pode acessar
5. THE System SHALL suportar permissões nos módulos: Criação de Sites, Gestão de Tarefas, Convidados, Financeiro, Relatórios e APP
6. WHEN um Convidado acessar o APP, THE System SHALL permitir apenas funcionalidades de convidado: notificações, gamification e confirmação de presença
7. IF um usuário tentar acessar um módulo sem permissão, THEN THE System SHALL retornar erro 403 Forbidden

### Requirement 3: Infraestrutura Docker

**User Story:** Como desenvolvedor, quero um ambiente Docker completo e isolado, para que eu possa desenvolver localmente simulando o ambiente de produção.

#### Acceptance Criteria

1. THE Docker_Stack SHALL incluir os serviços: nginx, php-fpm, postgres, redis e mailpit
2. WHILE o ambiente estiver em modo local, THE System SHALL utilizar o driver de log daily
3. WHILE o ambiente estiver em modo local, THE System SHALL utilizar o driver de fila redis
4. THE Container nginx SHALL rotear requisições para o container php-fpm na porta 9000
5. THE Container postgres SHALL persistir dados em um volume Docker nomeado
6. THE Container redis SHALL ser utilizado para cache, sessões e filas

### Requirement 4: Autenticação API com Sanctum

**User Story:** Como desenvolvedor, quero autenticação stateless via tokens, para que apps externos (como Gamification) possam consumir a API de forma segura.

#### Acceptance Criteria

1. THE System SHALL utilizar Laravel Sanctum para autenticação de APIs
2. WHEN um token de API for gerado, THE System SHALL vincular o token ao wedding_id e perfil do usuário
3. IF uma requisição API for feita sem token válido, THEN THE System SHALL retornar erro 401 Unauthorized
4. IF uma requisição API for feita com token não vinculado a um wedding_id válido, THEN THE System SHALL retornar erro 401 Unauthorized
5. WHERE o App de Gamification solicitar dados, THE System SHALL fornecer endpoints autenticados via Sanctum

### Requirement 5: Estrutura de Banco de Dados

**User Story:** Como desenvolvedor, quero que todas as tabelas relacionais possuam wedding_id indexado, para garantir performance e integridade dos dados.

#### Acceptance Criteria

1. WHEN uma nova migration for criada para tabela relacional, THE Migration SHALL incluir coluna wedding_id com foreign key para tabela weddings
2. WHEN uma nova migration for criada para tabela relacional, THE Migration SHALL criar índice na coluna wedding_id
3. THE Database SHALL utilizar PostgreSQL com suporte a campos JSONB
4. THE Table weddings SHALL armazenar configurações personalizadas em campo JSONB
5. THE Table users SHALL incluir coluna role para identificar o perfil do usuário
6. THE Table wedding_user SHALL armazenar a relação entre usuários e casamentos com permissões por módulo

### Requirement 6: Relatórios com IA

**User Story:** Como usuário, quero relatórios inteligentes gerados por IA, para ter insights sobre meu casamento de forma rápida.

#### Acceptance Criteria

1. THE System SHALL gerar relatórios utilizando integração com IA
2. WHEN um relatório for solicitado, THE System SHALL processar de forma síncrona (sem necessidade de fila)
3. THE Relatórios SHALL respeitar o wedding_id do usuário autenticado
4. IF um Organizador solicitar relatório, THEN THE System SHALL verificar se ele possui permissão no módulo Relatórios

### Requirement 7: Painel Administrativo

**User Story:** Como administrador do casamento, quero um painel de gestão profissional, para gerenciar tarefas, finanças e configurações.

#### Acceptance Criteria

1. THE System SHALL utilizar FilamentPHP para o painel administrativo
2. THE FilamentPHP resources SHALL estender WeddingScopedModel para isolamento de dados
3. WHEN um usuário acessar o painel, THE System SHALL exibir apenas dados do seu wedding_id
4. THE Painel SHALL verificar permissões do perfil antes de exibir cada módulo
5. WHEN um Organizador acessar o painel, THE System SHALL exibir apenas os módulos que ele tem permissão

### Requirement 8: Frontend com Inertia.js e Vue.js

**User Story:** Como desenvolvedor, quero uma stack frontend moderna e reativa, para criar interfaces de usuário profissionais.

#### Acceptance Criteria

1. THE System SHALL utilizar Inertia.js como bridge entre Laravel e Vue.js
2. THE Frontend SHALL utilizar Vue.js 3 com Composition API
3. THE Styling SHALL utilizar Tailwind CSS
4. WHEN uma página for renderizada, THE System SHALL passar o wedding_id, perfil e permissões do usuário autenticado para o frontend

### Requirement 9: Funcionalidades de Convidados

**User Story:** Como convidado, quero acessar o APP para interagir com o casamento, confirmando presença e participando de gamification.

#### Acceptance Criteria

1. WHEN um Convidado acessar o APP, THE System SHALL exibir notificações do casamento
2. WHEN um Convidado confirmar presença, THE System SHALL registrar a confirmação vinculada ao wedding_id
3. WHERE o módulo de Gamification estiver ativo, THE System SHALL permitir que Convidados participem das atividades
4. THE APP SHALL autenticar Convidados via Sanctum com token específico de convidado
