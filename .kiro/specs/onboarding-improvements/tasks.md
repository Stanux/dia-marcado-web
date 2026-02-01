# Plano de Implementação: Onboarding Improvements

## Visão Geral

Este plano implementa as melhorias no fluxo de onboarding (layout tela cheia, destaque na data) e a nova página de configurações do casamento para edição de dados e gerenciamento de parceiro.

## Tarefas

- [x] 1. Modificar layout da página de Onboarding
  - [x] 1.1 Criar view customizada para onboarding em tela cheia
    - Criar arquivo `resources/views/filament/pages/onboarding-fullscreen.blade.php`
    - Usar layout simples sem sidebar e header
    - Manter wizard centralizado
    - _Requisitos: 1.1, 1.2, 1.3, 1.4_
  - [x] 1.2 Atualizar OnboardingPage para usar nova view
    - Configurar `$view` para usar a view customizada
    - Garantir que navegação continua desabilitada
    - _Requisitos: 1.1, 1.2_

- [x] 2. Adicionar destaque visual no campo de data
  - [x] 2.1 Criar estilo CSS customizado para campo de data
    - Adicionar classe de destaque com cor diferenciada
    - Aplicar ao DatePicker do campo wedding_date
    - _Requisitos: 2.1, 2.2_
  - [x] 2.2 Atualizar schema do Step 1 com destaque visual
    - Aplicar extraAttributes com classe de destaque
    - Manter campo no topo da seção
    - _Requisitos: 2.1, 2.2, 2.3_

- [x] 3. Checkpoint - Verificar layout do onboarding
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [x] 4. Criar WeddingSettingsService
  - [x] 4.1 Criar interface WeddingSettingsServiceInterface
    - Criar arquivo `app/Contracts/WeddingSettingsServiceInterface.php`
    - Métodos: update, canEdit
    - _Requisitos: 3.3, 3.4_
  - [x] 4.2 Implementar WeddingSettingsService
    - Criar arquivo `app/Services/WeddingSettingsService.php`
    - Implementar método update para atualizar dados do casamento
    - Implementar método canEdit para verificar permissão
    - _Requisitos: 3.3, 3.4_
  - [x] 4.3 Registrar binding no AppServiceProvider
    - Bind interface para implementação concreta
    - _Requisitos: 3.3_
  - [x] 4.4 Escrever teste de propriedade para controle de acesso
    - **Propriedade 1: Controle de Acesso à Página de Configurações**
    - **Valida: Requisitos 3.4**
  - [x] 4.5 Escrever teste de propriedade para round-trip de persistência
    - **Propriedade 2: Round-Trip de Persistência de Dados**
    - **Valida: Requisitos 3.3, 4.3, 5.2, 6.2**

- [x] 5. Criar página WeddingSettings no Filament
  - [x] 5.1 Criar WeddingSettingsPage com estrutura básica
    - Criar arquivo `app/Filament/Pages/WeddingSettings.php`
    - Configurar navegação, ícone, título e slug
    - Implementar método canAccess para verificar papel "couple"
    - _Requisitos: 3.1, 3.4_
  - [x] 5.2 Implementar seção de Data do Casamento
    - Campo DatePicker para wedding_date
    - Validação de data futura
    - _Requisitos: 4.1, 4.2_
  - [x] 5.3 Implementar seção de Local do Evento
    - Campos: nome, endereço, bairro, cidade, estado, telefone
    - Todos os campos opcionais
    - _Requisitos: 5.1, 5.3_
  - [x] 5.4 Implementar seção de Plano
    - Radio buttons para Básico e Premium
    - Exibir plano atual selecionado
    - _Requisitos: 6.1, 6.3_
  - [x] 5.5 Implementar método save() para persistir alterações
    - Chamar WeddingSettingsService.update()
    - Exibir notificação de sucesso/erro
    - _Requisitos: 3.3_
  - [x] 5.6 Escrever teste de propriedade para validação de data futura
    - **Propriedade 3: Validação de Data Futura**
    - **Valida: Requisitos 4.2**
  - [x] 5.7 Escrever teste de propriedade para campos opcionais de local
    - **Propriedade 4: Campos de Local Opcionais**
    - **Valida: Requisitos 5.3**

- [x] 6. Checkpoint - Verificar página de configurações básica
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

- [x] 7. Implementar seção de Parceiro na página de configurações
  - [x] 7.1 Criar método para determinar status do parceiro
    - Verificar se casamento tem parceiro vinculado
    - Verificar se existe convite pendente/expirado/recusado
    - Retornar PartnerStatus apropriado
    - Usar PartnerInviteService existente para consultas
    - _Requisitos: 7.2, 7.3, 8.1, 8.2, 8.3, 8.4_
  - [x] 7.2 Implementar exibição de parceiro vinculado (read-only)
    - Exibir nome e e-mail do parceiro
    - Campos desabilitados
    - _Requisitos: 7.2, 8.2_
  - [x] 7.3 Implementar exibição de status de convite pendente
    - Exibir mensagem "Convite enviado - Aguardando aceite"
    - Exibir nome e e-mail do convite
    - Botão para reenviar convite
    - _Requisitos: 7.5, 8.1_
  - [x] 7.4 Implementar exibição de convite expirado/recusado
    - Exibir mensagem apropriada
    - Botão para enviar novo convite
    - _Requisitos: 8.3, 8.4_
  - [x] 7.5 Implementar formulário para novo convite de parceiro
    - Campos: nome e e-mail
    - Validação de e-mail válido e diferente do usuário
    - _Requisitos: 7.3, 7.6, 7.7_
  - [x] 7.6 Implementar envio de convite ao salvar
    - Chamar PartnerInviteService.sendInvite()
    - Atualizar UI com novo status
    - _Requisitos: 7.4_
  - [x] 7.7 Escrever teste de propriedade para estado dos campos de parceiro
    - **Propriedade 5: Estado dos Campos de Parceiro**
    - **Valida: Requisitos 7.2, 7.3**
  - [x] 7.8 Escrever teste de propriedade para criação de convite
    - **Propriedade 6: Criação de Convite ao Salvar Dados de Parceiro**
    - **Valida: Requisitos 7.4**
  - [x] 7.9 Escrever teste de propriedade para validação de e-mail
    - **Propriedade 7: Validação de E-mail do Parceiro**
    - **Valida: Requisitos 7.6, 7.7**
  - [x] 7.10 Escrever teste de propriedade para exibição de status
    - **Propriedade 8: Exibição Correta de Status do Convite**
    - **Valida: Requisitos 7.5, 8.1, 8.2, 8.3, 8.4**

- [x] 8. Implementar ação de reenviar convite
  - [x] 8.1 Criar método resendInvite na página
    - Verificar se existe convite pendente ou expirado
    - Chamar PartnerInviteService para reenviar
    - Atualizar status do convite
    - _Requisitos: 7.5, 8.3_
  - [x] 8.2 Adicionar botão de reenvio na UI
    - Exibir apenas quando há convite pendente ou expirado
    - Feedback visual de sucesso/erro
    - _Requisitos: 7.5, 8.3_

- [x] 9. Checkpoint final - Verificar fluxo completo
  - Garantir que todos os testes passam, perguntar ao usuário se houver dúvidas.

## Notas

- Todas as tarefas são obrigatórias, incluindo testes de propriedade
- Cada tarefa referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Testes de propriedade validam propriedades universais de corretude
- O projeto já usa PHP/Laravel com Filament, então seguimos os padrões existentes
- PartnerInviteService já existe e será reutilizado para funcionalidades de convite
