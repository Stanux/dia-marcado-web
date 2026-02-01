# Documento de Requisitos

## Introdução

Este documento define os requisitos para o fluxo de onboarding de configuração inicial que os usuários devem completar após criar sua conta de acesso. O onboarding é um processo de 3 etapas que coleta informações essenciais sobre o casal, local do evento e plano escolhido, culminando na criação automática de um site para o casamento.

## Glossário

- **Sistema_Onboarding**: Sistema responsável por guiar novos usuários através do processo de configuração inicial em múltiplas etapas
- **Componente_Timeline**: Componente visual no topo da tela que indica o progresso do usuário através das etapas do onboarding
- **Casamento**: Entidade que representa um casamento no sistema, contendo informações do casal e evento
- **Site_Layout**: Entidade que representa o site do casamento, com slug único para acesso público
- **Gerador_Slug**: Serviço responsável por gerar identificadores únicos para URLs de sites
- **Usuario_Parceiro**: Usuário criado automaticamente para o(a) noivo(a) parceiro(a) durante o onboarding
- **Plano**: Tipo de plano selecionado pelo usuário (Básico ou Premium)

## Requisitos

### Requisito 1: Redirecionamento para Onboarding

**História do Usuário:** Como um novo usuário, quero ser redirecionado para o fluxo de onboarding após o registro, para que eu possa configurar meu casamento antes de acessar o sistema.

#### Critérios de Aceitação

1. QUANDO um usuário tenta acessar qualquer página do sistema e não completou o onboarding ENTÃO O Sistema_Onboarding DEVE redirecionar o usuário para o fluxo de onboarding
2. QUANDO um usuário completou o onboarding com sucesso ENTÃO O Sistema_Onboarding DEVE permitir acesso ao dashboard e demais páginas do sistema
3. QUANDO um usuário está no meio do onboarding e atualiza a página ENTÃO O Sistema_Onboarding DEVE restaurar seu progresso para a etapa atual
4. O Sistema_Onboarding DEVE bloquear o acesso ao dashboard até que todas as etapas obrigatórias do onboarding sejam concluídas

### Requisito 2: Timeline de Progresso

**História do Usuário:** Como um usuário passando pelo onboarding, quero ver meu progresso através das etapas, para que eu saiba quanto falta para completar.

#### Critérios de Aceitação

1. O Componente_Timeline DEVE exibir 3 etapas visualmente no topo de todas as páginas do onboarding
2. QUANDO o usuário está em uma etapa específica ENTÃO O Componente_Timeline DEVE destacar a etapa atual como ativa
3. QUANDO o usuário completa uma etapa ENTÃO O Componente_Timeline DEVE marcar aquela etapa como concluída
4. O Componente_Timeline DEVE exibir os rótulos das etapas: "Dados do Casal", "Local do Evento", "Escolha do Plano"

### Requisito 3: Página 1 - Dados do Casal

**História do Usuário:** Como um usuário, quero fornecer informações sobre mim e opcionalmente sobre meu(minha) parceiro(a), para que o sistema possa convidá-lo(a) a participar.

#### Critérios de Aceitação

1. O Sistema_Onboarding DEVE exibir um campo em destaque rotulado "Dia Marcado" no topo da Página 1 para a data do casamento
2. O Sistema_Onboarding DEVE exibir o nome e e-mail do usuário atual como campos somente leitura na coluna da esquerda
3. O Sistema_Onboarding DEVE exibir campos de entrada opcionais para nome completo e e-mail do(a) parceiro(a) na coluna da direita
4. O Sistema_Onboarding DEVE exibir um disclaimer abaixo dos campos do(a) parceiro(a) informando que um convite será enviado para o(a) parceiro(a) aceitar participar do casamento
5. QUANDO o usuário preenche o campo de e-mail do(a) parceiro(a) ENTÃO O Sistema_Onboarding DEVE validar se é um formato de e-mail válido
6. QUANDO o usuário preenche o e-mail do(a) parceiro(a) ENTÃO O Sistema_Onboarding DEVE exigir que o nome completo também seja preenchido
7. QUANDO o usuário clica no botão continuar com dados válidos ENTÃO O Sistema_Onboarding DEVE prosseguir para a Página 2
8. O Sistema_Onboarding DEVE permitir que o usuário prossiga sem preencher os dados do(a) parceiro(a)

### Requisito 3.1: Convite para Parceiro(a) - Novo Usuário

**História do Usuário:** Como um usuário, quero convidar meu(minha) parceiro(a) que ainda não tem conta na plataforma, para que ele(a) possa participar do planejamento do casamento.

#### Critérios de Aceitação

1. QUANDO o e-mail do(a) parceiro(a) não existe na plataforma ENTÃO O Sistema_Onboarding DEVE enviar um e-mail de convite para criar conta
2. O e-mail de convite DEVE informar que foi enviado por "[Nome Completo do Criador]"
3. O e-mail de convite DEVE conter um link para criar conta e aceitar o convite
4. QUANDO o(a) parceiro(a) aceita o convite e cria a conta ENTÃO O Sistema_Onboarding DEVE vincular o(a) parceiro(a) ao casamento com papel "couple"
5. ENQUANTO o(a) parceiro(a) não aceitar o convite ENTÃO O Sistema_Onboarding DEVE manter o casamento apenas com o criador como "couple"

### Requisito 3.2: Convite para Parceiro(a) - Usuário Existente

**História do Usuário:** Como um usuário, quero convidar meu(minha) parceiro(a) que já tem conta na plataforma, para que ele(a) possa participar do meu casamento.

#### Critérios de Aceitação

1. QUANDO o e-mail do(a) parceiro(a) já existe na plataforma ENTÃO O Sistema_Onboarding DEVE enviar um e-mail de convite especial
2. O e-mail de convite DEVE informar que foi enviado por "[Nome Completo do Criador]"
3. O e-mail de convite DEVE exibir um disclaimer em destaque informando que o usuário já está vinculado a outro projeto de casamento
4. O e-mail de convite DEVE informar que ao aceitar, o usuário será desvinculado do casamento anterior e vinculado ao novo
5. QUANDO o(a) parceiro(a) aceita o convite ENTÃO O Sistema_Onboarding DEVE desvincular o usuário do casamento anterior
6. QUANDO o(a) parceiro(a) aceita o convite ENTÃO O Sistema_Onboarding DEVE vincular o usuário ao novo casamento com papel "couple"
7. SE o(a) parceiro(a) recusar o convite ENTÃO O Sistema_Onboarding DEVE manter o casamento apenas com o criador como "couple"

### Requisito 4: Página 2 - Local do Evento

**História do Usuário:** Como um usuário, quero fornecer informações do local, para que os detalhes do casamento estejam completos.

#### Critérios de Aceitação

1. O Sistema_Onboarding DEVE exibir campos de entrada para: nome do local, endereço, bairro, cidade, estado e telefone de contato
2. O Sistema_Onboarding DEVE marcar todos os campos do local do evento como opcionais
3. QUANDO o usuário clica em continuar ENTÃO O Sistema_Onboarding DEVE prosseguir para a Página 3 independente dos campos estarem preenchidos ou não
4. O Sistema_Onboarding DEVE exibir um botão voltar para retornar à Página 1
5. O Sistema_Onboarding DEVE permitir que o usuário pule esta etapa sem preencher nenhum campo

### Requisito 5: Página 3 - Escolha do Plano

**História do Usuário:** Como um usuário, quero selecionar um plano para o site do meu casamento, para que eu possa acessar os recursos apropriados.

#### Critérios de Aceitação

1. O Sistema_Onboarding DEVE exibir duas opções de plano: "Básico" e "Premium"
2. O Sistema_Onboarding DEVE pré-selecionar o plano "Básico" por padrão
3. O Sistema_Onboarding DEVE permitir que o usuário selecione apenas um plano por vez
4. O Sistema_Onboarding DEVE exibir um botão voltar para retornar à Página 2
5. O Sistema_Onboarding DEVE exibir um botão "Concluir" para finalizar o onboarding

### Requisito 6: Criação de Dados ao Concluir

**História do Usuário:** Como um usuário completando o onboarding, quero que todos os meus dados sejam salvos e um site criado automaticamente, para que eu possa começar a usar o sistema imediatamente.

#### Critérios de Aceitação

1. QUANDO o usuário clica em "Concluir" na Página 3 ENTÃO O Sistema_Onboarding DEVE criar um registro de Casamento com todas as informações fornecidas
2. QUANDO o usuário clica em "Concluir" ENTÃO O Sistema_Onboarding DEVE associar o usuário criador com o papel "couple" no casamento
3. QUANDO o usuário clica em "Concluir" e informou dados do(a) parceiro(a) ENTÃO O Sistema_Onboarding DEVE enviar o e-mail de convite apropriado
4. QUANDO o usuário clica em "Concluir" ENTÃO O Sistema_Onboarding DEVE criar automaticamente um Site_Layout para o casamento
5. O Gerador_Slug DEVE gerar um slug único para o site baseado nos nomes do casal
6. SE o slug gerado já existir ENTÃO O Gerador_Slug DEVE adicionar um sufixo numérico para garantir unicidade
7. QUANDO todos os dados são salvos com sucesso ENTÃO O Sistema_Onboarding DEVE redirecionar o usuário para o dashboard
8. SE um erro ocorrer durante a criação dos dados ENTÃO O Sistema_Onboarding DEVE exibir uma mensagem de erro e permitir nova tentativa
9. O Sistema_Onboarding DEVE marcar o usuário como tendo completado o onboarding

### Requisito 7: Validação de E-mail do Parceiro

**História do Usuário:** Como sistema, quero validar o e-mail do parceiro corretamente, para garantir que os convites sejam enviados adequadamente.

#### Critérios de Aceitação

1. QUANDO o usuário preenche o e-mail do(a) parceiro(a) ENTÃO O Sistema_Onboarding DEVE validar se é um formato de e-mail válido
2. SE o e-mail do(a) parceiro(a) for igual ao e-mail do usuário atual ENTÃO O Sistema_Onboarding DEVE exibir um erro informando que os e-mails devem ser diferentes
3. O Sistema_Onboarding DEVE verificar se o e-mail já existe na plataforma para determinar o tipo de convite a ser enviado

### Requisito 8: Persistência de Estado

**História do Usuário:** Como um usuário, quero que meu progresso seja salvo se eu sair e retornar, para que eu não perca os dados inseridos.

#### Critérios de Aceitação

1. QUANDO o usuário navega entre as páginas do onboarding ENTÃO O Sistema_Onboarding DEVE preservar todos os dados inseridos
2. QUANDO o usuário atualiza a página ENTÃO O Sistema_Onboarding DEVE restaurar os dados do formulário para a etapa atual
3. QUANDO o usuário faz logout e login novamente sem completar o onboarding ENTÃO O Sistema_Onboarding DEVE reiniciar da Página 1
