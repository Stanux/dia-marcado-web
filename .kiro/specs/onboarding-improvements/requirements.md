# Documento de Requisitos

## Introdução

Este documento define os requisitos para melhorias no fluxo de onboarding e criação de uma página de configurações do casamento. As melhorias incluem: tela de onboarding sem menu lateral, destaque visual no campo de data, e uma nova página para editar dados do casamento incluindo convite de parceiro(a).

## Glossário

- **Sistema_Onboarding**: Sistema responsável por guiar novos usuários através do processo de configuração inicial
- **Pagina_Onboarding**: Página de wizard de 3 etapas para configuração inicial do casamento
- **Pagina_Configuracoes**: Nova página para editar dados do casamento após o onboarding
- **Campo_Data_Casamento**: Campo de seleção de data do casamento com destaque visual
- **Usuario_Parceiro**: Usuário criado ou convidado para participar do casamento como parceiro(a)
- **Casamento**: Entidade que representa um casamento no sistema

## Requisitos

### Requisito 1: Layout da Página de Onboarding

**História do Usuário:** Como um novo usuário, quero ver a página de onboarding em tela cheia sem distrações, para que eu possa focar na configuração do meu casamento.

#### Critérios de Aceitação

1. QUANDO o usuário acessa a Pagina_Onboarding ENTÃO O Sistema_Onboarding DEVE ocultar o menu lateral de navegação
2. QUANDO o usuário acessa a Pagina_Onboarding ENTÃO O Sistema_Onboarding DEVE ocultar o título/header padrão do Filament
3. QUANDO o usuário acessa a Pagina_Onboarding ENTÃO O Sistema_Onboarding DEVE exibir o wizard centralizado na tela
4. A Pagina_Onboarding DEVE ter aparência de painel de entrada/boas-vindas

### Requisito 2: Destaque Visual do Campo de Data

**História do Usuário:** Como um usuário no onboarding, quero que o campo de data do casamento tenha destaque visual, para que eu identifique facilmente este campo importante.

#### Critérios de Aceitação

1. O Campo_Data_Casamento DEVE ter uma cor de destaque diferente dos demais campos
2. O Campo_Data_Casamento DEVE estar posicionado em destaque no topo da primeira etapa
3. QUANDO o Campo_Data_Casamento está em foco ENTÃO O Sistema_Onboarding DEVE manter o destaque visual

### Requisito 3: Página de Configurações do Casamento

**História do Usuário:** Como um usuário logado, quero acessar uma página para editar os dados do meu casamento, para que eu possa atualizar informações após o onboarding.

#### Critérios de Aceitação

1. O Sistema DEVE disponibilizar uma Pagina_Configuracoes acessível pelo menu lateral
2. A Pagina_Configuracoes DEVE exibir os dados atuais do casamento para edição
3. QUANDO o usuário salva alterações ENTÃO O Sistema DEVE atualizar os dados do Casamento
4. A Pagina_Configuracoes DEVE ser acessível apenas para usuários com papel "couple" no casamento

### Requisito 4: Edição de Data do Casamento

**História do Usuário:** Como um usuário, quero poder alterar a data do meu casamento, para que eu possa corrigir ou atualizar esta informação.

#### Critérios de Aceitação

1. A Pagina_Configuracoes DEVE exibir o campo de data do casamento atual
2. QUANDO o usuário altera a data ENTÃO O Sistema DEVE validar se é uma data futura
3. QUANDO o usuário salva a nova data ENTÃO O Sistema DEVE atualizar o Casamento

### Requisito 5: Edição de Local do Evento

**História do Usuário:** Como um usuário, quero poder alterar os dados do local do evento, para que eu possa atualizar informações do local.

#### Critérios de Aceitação

1. A Pagina_Configuracoes DEVE exibir os campos de local: nome, endereço, bairro, cidade, estado e telefone
2. QUANDO o usuário altera qualquer campo de local ENTÃO O Sistema DEVE permitir salvar as alterações
3. Todos os campos de local DEVEM ser opcionais

### Requisito 6: Edição de Plano

**História do Usuário:** Como um usuário, quero poder visualizar e alterar meu plano contratado, para que eu possa fazer upgrade se necessário.

#### Critérios de Aceitação

1. A Pagina_Configuracoes DEVE exibir o plano atual do casamento
2. QUANDO o usuário seleciona um novo plano ENTÃO O Sistema DEVE atualizar o plano do Casamento
3. O Sistema DEVE exibir as opções de plano: Básico e Premium

### Requisito 7: Convite de Parceiro(a) via Configurações

**História do Usuário:** Como um usuário, quero poder convidar meu(minha) parceiro(a) pela página de configurações, para que ele(a) possa participar do planejamento mesmo que eu não tenha convidado no onboarding.

#### Critérios de Aceitação

1. A Pagina_Configuracoes DEVE exibir uma seção para dados do(a) parceiro(a)
2. SE o casamento já tem um parceiro vinculado ENTÃO O Sistema DEVE exibir os dados do parceiro como somente leitura
3. SE o casamento não tem parceiro vinculado ENTÃO O Sistema DEVE exibir campos para nome e e-mail do parceiro
4. QUANDO o usuário preenche os dados do parceiro e salva ENTÃO O Sistema DEVE enviar um convite para o parceiro
5. SE já existe um convite pendente ENTÃO O Sistema DEVE exibir o status do convite e opção de reenviar
6. QUANDO o usuário preenche o e-mail do parceiro ENTÃO O Sistema DEVE validar se é um formato de e-mail válido
7. QUANDO o usuário preenche o e-mail do parceiro ENTÃO O Sistema DEVE validar se é diferente do e-mail do usuário atual

### Requisito 8: Exibição de Status do Parceiro

**História do Usuário:** Como um usuário, quero ver o status do convite do meu parceiro, para que eu saiba se ele já aceitou ou se preciso reenviar.

#### Critérios de Aceitação

1. SE existe um convite pendente ENTÃO O Sistema DEVE exibir "Convite enviado - Aguardando aceite"
2. SE o parceiro já aceitou ENTÃO O Sistema DEVE exibir o nome e e-mail do parceiro vinculado
3. SE o convite expirou ENTÃO O Sistema DEVE exibir "Convite expirado" com opção de reenviar
4. SE o convite foi recusado ENTÃO O Sistema DEVE exibir "Convite recusado" com opção de enviar novo convite

