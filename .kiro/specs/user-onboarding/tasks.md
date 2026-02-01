# Plano de Implementação: User Onboarding

## Visão Geral

Este plano implementa o fluxo de onboarding de configuração inicial em 3 etapas, incluindo criação de casamento, convite de parceiro e criação automática de site.

## Tarefas

- [x] 1. Configurar estrutura de banco de dados
  - [x] 1.1 Criar migration para adicionar campo `onboarding_completed` na tabela users
    - Adicionar coluna boolean `onboarding_completed` com default false
    - _Requisitos: 1.1, 1.2, 6.9_
  - [x] 1.2 Criar migration para tabela `partner_invites`
    - Campos: id, wedding_id, inviter_id, email, name, token, status, existing_user_id, previous_wedding_id, expires_at
    - Índices em email+status e token
    - _Requisitos: 3.1.1, 3.2.1_
  - [x] 1.3 Atualizar model User com campo e método `hasCompletedOnboarding()`
    - Adicionar campo ao fillable e cast
    - Implementar método de verificação
    - _Requisitos: 1.1, 1.2_

- [x] 2. Criar model PartnerInvite
  - [x] 2.1 Implementar model PartnerInvite com relacionamentos
    - Relacionamentos: wedding, inviter, existingUser, previousWedding
    - Scopes: pending, expired
    - _Requisitos: 3.1.1, 3.2.1_
  - [x] 2.2 Criar factory para PartnerInvite
    - Gerar dados de teste realistas
    - _Requisitos: 3.1.1, 3.2.1_

- [x] 3. Implementar OnboardingMiddleware
  - [x] 3.1 Criar middleware EnsureOnboardingComplete
    - Verificar se usuário completou onboarding
    - Redirecionar para página de onboarding se não completou
    - Permitir acesso à página de onboarding
    - Ignorar para usuários admin
    - _Requisitos: 1.1, 1.2, 1.4_
  - [x] 3.2 Registrar middleware no Filament AdminPanelProvider
    - Adicionar ao grupo de middlewares do painel
    - _Requisitos: 1.1_
  - [x] 3.3 Escrever teste de propriedade para controle de acesso
    - **Propriedade 1: Controle de Acesso Baseado em Status de Onboarding**
    - **Valida: Requisitos 1.1, 1.2, 1.4**

- [x] 4. Checkpoint - Verificar middleware funcionando
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [x] 5. Implementar PartnerInviteService
  - [x] 5.1 Criar interface PartnerInviteServiceInterface
    - Métodos: sendInvite, acceptInvite, declineInvite
    - _Requisitos: 3.1.1, 3.2.1_
  - [x] 5.2 Implementar PartnerInviteService
    - Lógica para verificar se e-mail existe na plataforma
    - Criar convite com token único
    - Determinar tipo de convite (novo usuário vs existente)
    - _Requisitos: 3.1.1, 3.2.1, 3.2.3_
  - [x] 5.3 Implementar método acceptInvite
    - Vincular usuário ao casamento como couple
    - Desvincular de casamento anterior se existir
    - Atualizar status do convite
    - _Requisitos: 3.1.4, 3.2.5, 3.2.6_
  - [x] 5.4 Implementar método declineInvite
    - Atualizar status do convite para declined
    - _Requisitos: 3.2.7_
  - [x] 5.5 Escrever teste de propriedade para tipo de convite
    - **Propriedade 4: Tipo de Convite Baseado em Existência do E-mail**
    - **Valida: Requisitos 3.1.1, 3.2.1, 3.2.3**
  - [x] 5.6 Escrever teste de propriedade para vinculação após aceite
    - **Propriedade 6: Vinculação de Parceiro Após Aceite**
    - **Valida: Requisitos 3.1.4, 3.2.5**

- [x] 6. Criar notificações de convite
  - [x] 6.1 Criar NewUserInviteNotification
    - E-mail para novos usuários com link de criação de conta
    - Incluir nome do convidador
    - _Requisitos: 3.1.1, 3.1.2, 3.1.3_
  - [x] 6.2 Criar ExistingUserInviteNotification
    - E-mail para usuários existentes com disclaimer de desvinculação
    - Incluir nome do convidador
    - Link para aceitar/recusar
    - _Requisitos: 3.2.1, 3.2.2, 3.2.3, 3.2.4_
  - [x] 6.3 Escrever teste de propriedade para conteúdo do e-mail
    - **Propriedade 5: Conteúdo do E-mail de Convite**
    - **Valida: Requisito 3.1.2**

- [x] 7. Checkpoint - Verificar serviço de convites
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [x] 8. Implementar OnboardingService
  - [x] 8.1 Criar interface OnboardingServiceInterface
    - Métodos: complete, hasCompleted
    - _Requisitos: 6.1, 6.9_
  - [x] 8.2 Implementar OnboardingService
    - Orquestrar criação de Wedding via WeddingService
    - Orquestrar criação de SiteLayout via SiteBuilderService
    - Enviar convite de parceiro se informado
    - Marcar onboarding como completo
    - Usar transação para garantir atomicidade
    - _Requisitos: 6.1, 6.2, 6.3, 6.4, 6.9_
  - [x] 8.3 Escrever teste de propriedade para criação de dados
    - **Propriedade 8: Criação de Dados ao Concluir Onboarding**
    - **Valida: Requisitos 6.1, 6.2, 6.4, 6.9**
  - [x] 8.4 Escrever teste de propriedade para estado do casamento
    - **Propriedade 7: Estado do Casamento Antes do Aceite**
    - **Valida: Requisitos 3.1.5, 3.2.7**

- [x] 9. Criar página de Onboarding no Filament
  - [x] 9.1 Criar OnboardingPage com Wizard de 3 etapas
    - Configurar página sem navegação
    - Implementar timeline visual com 3 steps
    - _Requisitos: 2.1, 2.2, 2.3, 2.4_
  - [x] 9.2 Implementar Step 1 - Dados do Casal
    - Campo "Dia Marcado" em destaque (DatePicker)
    - Campos read-only para nome e e-mail do usuário atual
    - Campos opcionais para nome e e-mail do parceiro
    - Disclaimer sobre convite
    - Validação de e-mail
    - _Requisitos: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_
  - [x] 9.3 Implementar Step 2 - Local do Evento
    - Campos opcionais: nome do local, endereço, bairro, cidade, estado, telefone
    - Botão voltar
    - _Requisitos: 4.1, 4.2, 4.3, 4.4, 4.5_
  - [x] 9.4 Implementar Step 3 - Escolha do Plano
    - Radio buttons para Básico e Premium
    - Básico pré-selecionado
    - Botão voltar e Concluir
    - _Requisitos: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [x] 9.5 Implementar método complete() que chama OnboardingService
    - Coletar dados do formulário
    - Chamar OnboardingService.complete()
    - Redirecionar para dashboard
    - Tratar erros com notificação
    - _Requisitos: 6.7, 6.8_
  - [x] 9.6 Escrever teste de propriedade para validação de e-mail
    - **Propriedade 2: Validação de E-mail do Parceiro**
    - **Valida: Requisitos 3.5, 7.2**
  - [x] 9.7 Escrever teste de propriedade para dependência de campos
    - **Propriedade 3: Dependência de Campos do Parceiro**
    - **Valida: Requisito 3.6**

- [x] 10. Checkpoint - Verificar página de onboarding
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [x] 11. Implementar persistência de estado
  - [x] 11.1 Configurar persistência de dados do wizard na sessão
    - Salvar dados ao navegar entre steps
    - Restaurar dados ao carregar página
    - _Requisitos: 1.3, 8.1, 8.2_
  - [x] 11.2 Implementar limpeza de sessão após logout
    - Limpar dados de onboarding da sessão no logout
    - _Requisitos: 8.3_
  - [x] 11.3 Escrever teste de propriedade para persistência
    - **Propriedade 10: Persistência de Estado do Onboarding**
    - **Valida: Requisitos 1.3, 8.1**
  - [x] 11.4 Escrever teste de propriedade para reset após logout
    - **Propriedade 11: Reset de Onboarding Após Logout**
    - **Valida: Requisito 8.3**

- [x] 12. Criar página de aceite/recusa de convite
  - [x] 12.1 Criar rota pública para aceite de convite
    - Validar token do convite
    - Verificar se convite não expirou
    - _Requisitos: 3.1.3, 3.1.4_
  - [x] 12.2 Criar página para novos usuários aceitarem convite
    - Formulário de criação de conta
    - Após criar conta, vincular ao casamento
    - _Requisitos: 3.1.3, 3.1.4_
  - [x] 12.3 Criar página para usuários existentes aceitarem/recusarem
    - Mostrar disclaimer sobre desvinculação
    - Botões aceitar/recusar
    - _Requisitos: 3.2.3, 3.2.4, 3.2.5, 3.2.6, 3.2.7_

- [x] 13. Integrar com SlugGeneratorService existente
  - [x] 13.1 Verificar que SiteBuilderService usa SlugGeneratorService
    - Garantir que slug é gerado baseado nos nomes do casal
    - Garantir unicidade com sufixo numérico
    - _Requisitos: 6.5, 6.6_
  - [x] 13.2 Escrever teste de propriedade para unicidade de slug
    - **Propriedade 9: Unicidade de Slug**
    - **Valida: Requisitos 6.5, 6.6**

- [x] 14. Registrar bindings no ServiceProvider
  - [x] 14.1 Registrar OnboardingServiceInterface no AppServiceProvider
    - Bind interface para implementação concreta
    - _Requisitos: 6.1_
  - [x] 14.2 Registrar PartnerInviteServiceInterface no AppServiceProvider
    - Bind interface para implementação concreta
    - _Requisitos: 3.1.1_

- [x] 15. Checkpoint final - Verificar fluxo completo
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

## Notas

- Todas as tarefas são obrigatórias, incluindo testes de propriedade
- Cada tarefa referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Testes de propriedade validam propriedades universais de corretude
- Testes unitários validam exemplos específicos e casos de borda
