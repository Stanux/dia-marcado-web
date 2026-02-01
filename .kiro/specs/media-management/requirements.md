# Documento de Requisitos

## Introdução

Este documento especifica os requisitos para o Módulo de Gerenciamento de Mídia da plataforma SaaS de casamentos. O módulo permitirá upload assíncrono de múltiplos arquivos, organização em álbuns por tipo, configuração de parâmetros administrativos, controle de limites por plano de assinatura, rastreamento de cotas e integração com o construtor de sites.

## Glossário

- **Sistema_Midia**: O módulo de gerenciamento de mídia responsável por upload, armazenamento e organização de arquivos
- **Album**: Coleção de arquivos de mídia agrupados por um tema ou evento específico
- **Tipo_Album**: Categoria que classifica álbuns (Pré Casamento, Pós Casamento, Uso do Site)
- **Arquivo_Midia**: Um arquivo de imagem ou vídeo enviado pelo usuário
- **Cota**: Limite de uso de recursos (quantidade de arquivos ou espaço de armazenamento)
- **Plano_Assinatura**: Nível de serviço contratado pelo usuário (básico ou premium)
- **Construtor_Site**: Interface para personalização do site de casamento
- **Fila_Upload**: Sistema de processamento assíncrono de uploads
- **Widget_Dashboard**: Componente visual que exibe informações resumidas no painel principal

## Requisitos

### Requisito 1: Upload Assíncrono de Múltiplos Arquivos

**História do Usuário:** Como usuário, quero fazer upload de múltiplos arquivos simultaneamente, para que eu possa adicionar várias fotos e vídeos de forma eficiente.

#### Critérios de Aceitação

1. QUANDO o usuário seleciona múltiplos arquivos para upload, ENTÃO o Sistema_Midia DEVERÁ criar uma entrada na Fila_Upload para cada arquivo
2. QUANDO um arquivo é adicionado à Fila_Upload, ENTÃO o Sistema_Midia DEVERÁ processar o upload de forma assíncrona sem bloquear a interface
3. ENQUANTO arquivos estão sendo processados, o Sistema_Midia DEVERÁ exibir o progresso individual de cada upload
4. QUANDO um upload individual falha, ENTÃO o Sistema_Midia DEVERÁ continuar processando os demais arquivos e registrar o erro
5. QUANDO todos os uploads de um lote são concluídos, ENTÃO o Sistema_Midia DEVERÁ notificar o usuário com um resumo (sucessos e falhas)
6. SE o usuário cancelar um upload em andamento, ENTÃO o Sistema_Midia DEVERÁ interromper o processamento e remover arquivos parciais

### Requisito 2: Organização de Álbuns por Tipo

**História do Usuário:** Como usuário, quero organizar minhas mídias em álbuns categorizados por tipo, para que eu possa encontrar e gerenciar facilmente minhas fotos e vídeos.

#### Critérios de Aceitação

1. O Sistema_Midia DEVERÁ suportar três Tipos_Album: "Pré Casamento", "Pós Casamento" e "Uso do Site"
2. QUANDO o usuário cria um novo Album, ENTÃO o Sistema_Midia DEVERÁ exigir a seleção de um Tipo_Album
3. QUANDO um Arquivo_Midia é enviado, ENTÃO o Sistema_Midia DEVERÁ exigir a associação a um Album existente
4. QUANDO o usuário visualiza a lista de álbuns, ENTÃO o Sistema_Midia DEVERÁ agrupar os álbuns por Tipo_Album
5. QUANDO o usuário move um Arquivo_Midia entre álbuns, ENTÃO o Sistema_Midia DEVERÁ atualizar a associação mantendo o histórico
6. SE um Album é excluído, ENTÃO o Sistema_Midia DEVERÁ solicitar confirmação e oferecer opção de mover arquivos para outro álbum

### Requisito 3: Parâmetros de Configuração Administrativa

**História do Usuário:** Como administrador, quero configurar limites de tamanho e dimensões de arquivos, para que eu possa controlar o uso de recursos do sistema.

#### Critérios de Aceitação

1. O Sistema_Midia DEVERÁ permitir configuração de dimensões máximas de imagem (largura e altura em pixels)
2. O Sistema_Midia DEVERÁ permitir configuração de tamanho máximo de arquivo de imagem (em bytes)
3. O Sistema_Midia DEVERÁ permitir configuração de tamanho máximo de arquivo de vídeo (em bytes)
4. QUANDO um arquivo excede as dimensões máximas configuradas, ENTÃO o Sistema_Midia DEVERÁ redimensionar automaticamente a imagem
5. QUANDO um arquivo excede o tamanho máximo configurado, ENTÃO o Sistema_Midia DEVERÁ rejeitar o upload com mensagem explicativa
6. QUANDO as configurações são alteradas, ENTÃO o Sistema_Midia DEVERÁ aplicar as novas regras apenas a uploads futuros

### Requisito 4: Limites por Plano de Assinatura

**História do Usuário:** Como administrador, quero definir limites diferentes de arquivos e armazenamento por plano, para que eu possa oferecer níveis de serviço diferenciados.

#### Critérios de Aceitação

1. O Sistema_Midia DEVERÁ suportar configuração de número máximo de arquivos por Plano_Assinatura
2. O Sistema_Midia DEVERÁ suportar configuração de espaço máximo de armazenamento por Plano_Assinatura
3. QUANDO o usuário tenta fazer upload e atingiu o limite de arquivos do plano, ENTÃO o Sistema_Midia DEVERÁ bloquear o upload
4. QUANDO o usuário tenta fazer upload e atingiu o limite de armazenamento do plano, ENTÃO o Sistema_Midia DEVERÁ bloquear o upload
5. O Sistema_Midia DEVERÁ manter limites distintos para plano básico e plano premium
6. QUANDO um usuário faz upgrade de plano, ENTÃO o Sistema_Midia DEVERÁ aplicar os novos limites imediatamente

### Requisito 5: Rastreamento e Exibição de Cotas

**História do Usuário:** Como usuário, quero visualizar meu uso de cota de armazenamento, para que eu possa gerenciar meus arquivos e decidir sobre upgrade de plano.

#### Critérios de Aceitação

1. O Sistema_Midia DEVERÁ exibir um Widget_Dashboard mostrando uso atual de cota (arquivos e armazenamento)
2. O Sistema_Midia DEVERÁ exibir informações de cota no módulo de gerenciamento de mídia
3. QUANDO o usuário atinge 80% da cota, ENTÃO o Sistema_Midia DEVERÁ exibir um alerta visual
4. QUANDO o usuário do plano básico atinge 100% da cota, ENTÃO o Sistema_Midia DEVERÁ oferecer upgrade para plano premium
5. O Sistema_Midia DEVERÁ exibir a cota em formato percentual e valores absolutos (ex: "150/200 arquivos - 75%")
6. QUANDO arquivos são excluídos, ENTÃO o Sistema_Midia DEVERÁ atualizar a exibição de cota em tempo real

### Requisito 6: Integração com Construtor de Sites

**História do Usuário:** Como usuário, quero selecionar imagens da minha biblioteca de mídia ao personalizar meu site, para que eu possa reutilizar fotos já enviadas.

#### Critérios de Aceitação

1. QUANDO o usuário edita um componente de imagem no Construtor_Site, ENTÃO o Sistema_Midia DEVERÁ oferecer opção de selecionar mídia existente
2. QUANDO o usuário seleciona "escolher da biblioteca", ENTÃO o Sistema_Midia DEVERÁ exibir galeria navegável por álbum e tipo
3. QUANDO o usuário faz upload de nova imagem durante edição do site, ENTÃO o Sistema_Midia DEVERÁ solicitar seleção de Tipo_Album e Album
4. SE o usuário não possui álbuns, ENTÃO o Sistema_Midia DEVERÁ permitir criação de álbum durante o fluxo de upload
5. QUANDO uma mídia é selecionada no Construtor_Site, ENTÃO o Sistema_Midia DEVERÁ retornar a URL otimizada apropriada
6. O Sistema_Midia DEVERÁ exibir preview da mídia antes da confirmação de seleção

### Requisito 7: Validação e Segurança de Arquivos

**História do Usuário:** Como administrador, quero garantir que apenas arquivos seguros e válidos sejam aceitos, para que eu possa proteger o sistema e os usuários.

#### Critérios de Aceitação

1. QUANDO um arquivo é enviado, ENTÃO o Sistema_Midia DEVERÁ validar o tipo MIME contra a extensão declarada
2. QUANDO um arquivo é enviado, ENTÃO o Sistema_Midia DEVERÁ escanear por assinaturas maliciosas
3. SE um arquivo falha na validação de segurança, ENTÃO o Sistema_Midia DEVERÁ rejeitar o upload e registrar o incidente
4. O Sistema_Midia DEVERÁ aceitar apenas extensões permitidas: jpg, jpeg, png, gif, webp, mp4, webm
5. QUANDO uma imagem é processada, ENTÃO o Sistema_Midia DEVERÁ gerar variantes otimizadas (thumbnail, webp, 1x/2x)
6. O Sistema_Midia DEVERÁ armazenar arquivos com nomes UUID para prevenir conflitos e exposição de informações

### Requisito 8: Gerenciamento de Arquivos

**História do Usuário:** Como usuário, quero gerenciar meus arquivos de mídia (visualizar, renomear, excluir, mover), para que eu possa manter minha biblioteca organizada.

#### Critérios de Aceitação

1. QUANDO o usuário visualiza a biblioteca de mídia, ENTÃO o Sistema_Midia DEVERÁ exibir thumbnails com informações básicas (nome, tamanho, data)
2. QUANDO o usuário seleciona um arquivo, ENTÃO o Sistema_Midia DEVERÁ exibir detalhes completos e opções de ação
3. QUANDO o usuário exclui um arquivo, ENTÃO o Sistema_Midia DEVERÁ remover o arquivo e todas as suas variantes
4. QUANDO o usuário move um arquivo para outro álbum, ENTÃO o Sistema_Midia DEVERÁ atualizar a associação sem duplicar o arquivo
5. O Sistema_Midia DEVERÁ suportar seleção múltipla para operações em lote (excluir, mover)
6. QUANDO o usuário busca arquivos, ENTÃO o Sistema_Midia DEVERÁ filtrar por nome, tipo de arquivo e álbum
