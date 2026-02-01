# Requirements Document

## Introduction

Este documento especifica os requisitos para o módulo de Criação de Sites do SaaS de Casamentos. O módulo permite que casais criem e personalizem sites de casamento com seções configuráveis (Cabeçalho, Hero, Save the Date, Catálogo de Presentes, RSVP, Galeria de Fotos e Rodapé). O sistema utiliza armazenamento JSON para flexibilidade, suporta rascunhos com versionamento, e permite publicação com proteção por senha simples.

## Glossary

- **Site_Layout**: Estrutura JSON que armazena todas as configurações e conteúdo do site de um casamento.
- **Draft**: Versão de trabalho do site que não está publicada. Todas as edições são salvas como rascunho.
- **Published_Snapshot**: Versão congelada do site que está visível publicamente.
- **Section**: Bloco configurável do site (Header, Hero, SaveTheDate, GiftRegistry, RSVP, PhotoGallery, Footer).
- **Template**: Modelo pré-definido com configurações iniciais de cores, tipografia e layout.
- **Access_Token**: Senha simples definida pelo casal para proteger o acesso público ao site.
- **Site_Slug**: Identificador único do site na URL (ex: vinicius-e-lilian).
- **Version**: Snapshot histórico do rascunho para permitir restauração.
- **QA_Checklist**: Validação automática de requisitos antes da publicação.
- **System_Config**: Tabela de configurações globais acessível apenas por Administradores.

## Requirements

### Requirement 1: Disponibilidade e Acesso ao Módulo

**User Story:** Como casal com plano ativo, quero acessar o módulo de criação de sites para personalizar meu site de casamento.

#### Acceptance Criteria

1. THE System SHALL disponibilizar o módulo de Sites apenas para weddings com campo `is_active = true`
2. WHEN um usuário Admin ou Noivo acessar a plataforma, THE System SHALL exibir o menu "Site" → "Layouts" sem necessidade de recarga
3. WHEN um Organizador com permissão no módulo "sites" acessar a plataforma, THE System SHALL exibir o menu "Site" → "Layouts"
4. IF um Convidado tentar acessar o módulo de Sites, THEN THE System SHALL retornar erro 403 Forbidden
5. IF um Organizador sem permissão no módulo "sites" tentar acessar, THEN THE System SHALL retornar erro 403 Forbidden
6. THE System SHALL permitir apenas 1 site ativo por wedding

### Requirement 2: Armazenamento e Estrutura de Dados

**User Story:** Como desenvolvedor, quero armazenar os layouts em formato JSON flexível para facilitar serialização e evolução do schema.

#### Acceptance Criteria

1. THE System SHALL armazenar o conteúdo do site em campo JSONB na tabela `site_layouts`
2. THE Table site_layouts SHALL conter: id, wedding_id, draft_content (JSONB), published_content (JSONB), slug, access_token, is_published, published_at, created_at, updated_at
3. WHEN um site for criado, THE System SHALL inicializar draft_content com estrutura padrão contendo todas as seções
4. THE System SHALL criar índice na coluna wedding_id para performance
5. THE System SHALL criar índice único na coluna slug para garantir unicidade

### Requirement 3: Sistema de Rascunho e Publicação

**User Story:** Como casal, quero que minhas alterações fiquem em rascunho até eu decidir publicar, para evitar mudanças acidentais no site público.

#### Acceptance Criteria

1. WHEN qualquer alteração for feita no editor, THE System SHALL salvar automaticamente no campo draft_content
2. WHEN o usuário clicar em "Publicar", THE System SHALL copiar draft_content para published_content e atualizar published_at
3. WHEN o layout estiver em modo rascunho (draft_content != published_content), THE Preview SHALL exibir watermark "RASCUNHO"
4. THE System SHALL manter draft_content e published_content separados para permitir edição contínua
5. WHEN um site for publicado, THE System SHALL enviar email de notificação para os Noivos do casamento

### Requirement 4: Versionamento e Histórico

**User Story:** Como casal, quero poder restaurar versões anteriores do meu site caso eu faça alterações indesejadas.

#### Acceptance Criteria

1. THE System SHALL criar uma nova versão na tabela `site_versions` a cada salvamento significativo do rascunho
2. THE System SHALL armazenar até N versões por site, onde N é configurável na System_Config (padrão: 30)
3. WHEN o limite de versões for atingido, THE System SHALL remover a versão mais antiga
4. WHEN um usuário solicitar restauração, THE System SHALL copiar o conteúdo da versão selecionada para draft_content
5. WHEN um site for publicado, THE System SHALL criar snapshot na tabela `site_versions` com flag is_published = true
6. THE System SHALL registrar user_id, timestamp e sumário de mudanças em cada versão

### Requirement 5: URL e Acesso Público

**User Story:** Como casal, quero um endereço único para meu site que seja fácil de compartilhar com os convidados.

#### Acceptance Criteria

1. WHEN um site for criado, THE System SHALL sugerir slug baseado nos nomes dos noivos (ex: vinicius-e-lilian)
2. THE System SHALL validar unicidade do slug antes de salvar
3. IF o slug sugerido já existir, THEN THE System SHALL sugerir alternativas com sufixo numérico
4. THE System SHALL permitir que o casal defina um domínio customizado (armazenado em custom_domain)
5. WHEN um visitante acessar o site público, THE System SHALL verificar o access_token se configurado
6. IF access_token estiver definido e visitante não fornecer senha correta, THEN THE System SHALL exibir tela de senha
7. THE URL padrão SHALL seguir o formato: `{dominio_plataforma}/{slug}`

### Requirement 6: Proteção por Senha

**User Story:** Como casal, quero proteger meu site com uma senha simples para compartilhar apenas com convidados.

#### Acceptance Criteria

1. THE System SHALL permitir definir access_token (senha simples) nas configurações do site
2. WHEN access_token estiver definido, THE System SHALL exibir formulário de senha antes de mostrar o conteúdo
3. THE System SHALL armazenar a sessão do visitante após autenticação bem-sucedida (cookie/session)
4. THE System SHALL permitir remover o access_token para tornar o site público
5. IF visitante inserir senha incorreta 5 vezes, THEN THE System SHALL bloquear acesso por 15 minutos

### Requirement 7: Pré-visualização Responsiva

**User Story:** Como casal, quero visualizar como meu site ficará em diferentes dispositivos antes de publicar.

#### Acceptance Criteria

1. THE Editor SHALL apresentar pré-visualização em 3 breakpoints: mobile (375px), tablet (768px), desktop (1280px)
2. WHEN o layout estiver em modo rascunho, THE Preview SHALL exibir watermark "RASCUNHO" em todas as visualizações
3. WHEN usuário clicar em "Visualizar como convidado", THE System SHALL renderizar sem controles de edição
4. THE Preview "como convidado" SHALL exibir dados reais do wedding (nomes, data, local) nos placeholders

### Requirement 8: Seção Cabeçalho (Header)

**User Story:** Como casal, quero personalizar o cabeçalho do site com nosso logotipo, título e menu de navegação.

#### Acceptance Criteria

1. THE Header Section SHALL suportar: logotipo (upload/URL), título principal, subtítulo, data do evento, menu de navegação, botão de ação
2. WHEN um logotipo for enviado, THE System SHALL gerar versões otimizadas (webp, 2x) e sugerir recorte
3. THE System SHALL aceitar rich text básico (negrito, itálico, link) nos campos de texto
4. THE System SHALL substituir placeholders ({noivo}, {noiva}, {data}) com dados do wedding
5. THE System SHALL permitir configurar: altura, espaçamento, alinhamento, cor de fundo, overlay, bordas
6. WHEN menu fixo (sticky) for habilitado, THE Header SHALL permanecer visível durante scroll
7. THE System SHALL permitir configurar botões com: rótulo, destino (âncora/URL/RSVP), cor, estilo, ícone

### Requirement 9: Seção Destaque (Hero)

**User Story:** Como casal, quero uma seção de destaque impactante com imagem/vídeo e chamada para ação.

#### Acceptance Criteria

1. THE Hero Section SHALL suportar: imagem/galeria/vídeo (upload/URL/YouTube/Vimeo), título, subtítulo, botões CTA, elementos decorativos
2. WHEN vídeo YouTube/Vimeo for inserido, THE System SHALL oferecer opções: autoplay (desktop), loop, fallback de imagem
3. THE System SHALL aceitar rich text e placeholders nos campos de texto
4. THE System SHALL permitir layouts: full-bleed, boxed, split (imagem/texto)
5. THE System SHALL permitir configurar: alinhamento, overlay com opacidade, tipografia, animações de entrada
6. IF contraste texto/fundo for inferior a WCAG AA, THEN THE Editor SHALL exibir aviso e sugestões de cor

### Requirement 10: Seção Save the Date

**User Story:** Como casal, quero exibir a data, local e um contador regressivo para o casamento.

#### Acceptance Criteria

1. THE SaveTheDate Section SHALL incluir: data/horário, local (nome + endereço), mapa embutido, descrição, contador regressivo
2. WHEN mapa for ativado, THE System SHALL usar API Google Maps/Mapbox com chave global configurada em System_Config
3. WHEN endereço for inválido para geolocalização, THE System SHALL exibir campo para coordenadas manuais
4. THE System SHALL permitir links para rota (Google Maps) e para seção RSVP
5. THE System SHALL permitir configurar formato do contador (dias/horas/minutos), cores, ícones, layout
6. THE System SHALL permitir botão "Adicionar ao calendário" gerando arquivo .ics dinamicamente

### Requirement 11: Seção Catálogo de Presentes (Mockup)

**User Story:** Como casal, quero exibir uma prévia do catálogo de presentes no site (integração futura com módulo de Presentes).

#### Acceptance Criteria

1. THE GiftRegistry Section SHALL exibir representação visual mockup de um catálogo de presentes
2. THE System SHALL permitir ativar/desativar a seção no site
3. THE System SHALL permitir customizar cor de fundo do bloco
4. THE Section SHALL exibir placeholder indicando "Catálogo de Presentes - Em breve" ou dados mockados
5. WHEN módulo de Presentes for implementado, THE System SHALL integrar dados reais (requisito futuro)

### Requirement 12: Seção RSVP (Mockup)

**User Story:** Como casal, quero exibir uma seção de confirmação de presença no site (integração futura com módulo de Convidados).

#### Acceptance Criteria

1. THE RSVP Section SHALL exibir representação visual mockup de formulário de confirmação
2. THE System SHALL permitir ativar/desativar a seção no site
3. THE Section SHALL exibir campos mockados: nome, email, confirmação, número de acompanhantes
4. THE System SHALL permitir customizar cor de fundo e textos da seção
5. WHEN módulo de Convidados for implementado, THE System SHALL integrar dados reais (requisito futuro)

### Requirement 13: Seção Galeria de Fotos

**User Story:** Como casal, quero exibir fotos do nosso relacionamento organizadas em álbuns.

#### Acceptance Criteria

1. THE PhotoGallery Section SHALL suportar dois álbuns: "Antes" e "Depois" com múltiplas coleções
2. WHEN upload em lote for realizado, THE System SHALL gerar miniaturas e versões responsivas (webp/jpg 1x/2x)
3. THE System SHALL permitir edição rápida: crop, rotação
4. THE System SHALL permitir para cada foto: título, legenda, data, localização, tags, créditos
5. THE System SHALL permitir layouts: masonry, grid, slideshow com lightbox
6. THE System SHALL permitir ativar/desativar download de fotos
7. IF foto marcada como "privada", THEN THE System SHALL ocultar da visualização pública
8. THE System SHALL permitir slider comparativo antes/depois

### Requirement 14: Seção Rodapé (Footer)

**User Story:** Como casal, quero um rodapé com links de redes sociais e informações legais.

#### Acceptance Criteria

1. THE Footer Section SHALL suportar: redes sociais, textos legais, botão voltar ao topo
2. THE System SHALL aceitar rich text e placeholders nos campos de texto
3. IF campo "copyright year" estiver vazio, THEN THE System SHALL preencher automaticamente com ano atual
4. THE System SHALL permitir configurar: cores, tipografia, altura mínima, borda superior
5. IF "exibir política de privacidade" estiver habilitado, THEN THE Footer SHALL exibir link obrigatório
6. THE System SHALL permitir layout responsivo com múltiplas colunas

### Requirement 15: Templates e Modelos

**User Story:** Como casal, quero escolher um modelo pré-definido para começar rapidamente a personalização.

#### Acceptance Criteria

1. THE System SHALL oferecer galeria de templates pré-definidos
2. WHEN template for selecionado, THE System SHALL aplicar configurações iniciais (cores, tipografia, espaçamento)
3. THE System SHALL permitir override de configurações do template por seção
4. THE System SHALL permitir salvar layout customizado como "modelo privado" para reutilização
5. THE Templates SHALL ser armazenados na tabela `site_templates` com estrutura JSON

### Requirement 16: Upload de Mídia e Segurança

**User Story:** Como plataforma, quero garantir que uploads de mídia sejam seguros e otimizados.

#### Acceptance Criteria

1. THE System SHALL verificar extensão permitida antes de aceitar upload (jpg, jpeg, png, gif, webp, mp4, webm)
2. THE System SHALL verificar tamanho máximo por arquivo (configurável em System_Config, padrão: 10MB)
3. THE System SHALL verificar tipo MIME real do arquivo
4. THE System SHALL realizar escaneamento básico contra conteúdo malicioso usando ClamAV ou similar
5. THE System SHALL rejeitar arquivos executáveis (.exe, .bat, .sh, .php)
6. THE System SHALL armazenar arquivos em diretório segmentado por wedding_id
7. THE System SHALL otimizar imagens automaticamente (compressão adaptativa)
8. IF upload exceder quota do wedding, THEN THE System SHALL rejeitar com mensagem informativa

### Requirement 17: Validações e QA Automático

**User Story:** Como plataforma, quero garantir que sites publicados atendam padrões mínimos de qualidade.

#### Acceptance Criteria

1. THE Editor SHALL impedir publicação se campos obrigatórios estiverem vazios (título do evento)
2. WHEN publicação for tentada com erros, THE System SHALL exibir lista de erros por seção com foco no primeiro
3. THE System SHALL validar URLs externas (HTTP/HTTPS válido)
4. THE System SHALL destacar imagens sem texto alternativo (alt) como erro
5. THE System SHALL executar checklist QA: imagens com alt, links válidos, campos obrigatórios, contraste WCAG
6. IF QA falhar, THEN THE System SHALL marcar site com status "Rever" e bloquear publicação até resolução
7. THE System SHALL permitir override de bloqueio por usuário com permissão (com registro em log)

### Requirement 18: Performance e Otimização

**User Story:** Como plataforma, quero que os sites carreguem rapidamente para boa experiência dos visitantes.

#### Acceptance Criteria

1. THE System SHALL otimizar imagens automaticamente com compressão adaptativa
2. THE System SHALL aplicar lazy-loading em galerias e vídeos
3. IF página exceder tamanho total de recursos (configurável em System_Config, padrão: 5MB), THEN THE Editor SHALL emitir alerta
4. THE System SHALL gerar meta tags SEO: title, description, Open Graph image, canonical
5. THE System SHALL permitir editar meta tags por página

### Requirement 19: Rollback e Recuperação

**User Story:** Como casal, quero poder reverter para uma versão anterior do site publicado em caso de problemas.

#### Acceptance Criteria

1. WHEN erro crítico pós-publicação for detectado (ex: link quebrado 5xx), THE System SHALL permitir rollback com um clique
2. THE System SHALL manter última versão publicada estável para rollback
3. WHEN rollback for executado, THE System SHALL restaurar published_content da versão anterior
4. THE System SHALL registrar todas as ações de rollback com user_id e timestamp

### Requirement 20: Sanitização de Conteúdo

**User Story:** Como plataforma, quero garantir que conteúdo malicioso não seja injetado nos sites.

#### Acceptance Criteria

1. THE System SHALL sanitizar qualquer tentativa de inserir tags `<script>` no conteúdo
2. THE System SHALL registrar em log tentativas de inserção de scripts
3. THE System SHALL sanitizar atributos de eventos inline (onclick, onerror, etc.)
4. THE System SHALL permitir apenas HTML seguro nos campos rich text

### Requirement 21: Configurações Globais (System_Config)

**User Story:** Como Administrador, quero configurar parâmetros globais do módulo de Sites.

#### Acceptance Criteria

1. THE System_Config SHALL ser acessível apenas por usuários Admin
2. THE System_Config SHALL incluir: max_file_size, max_versions, max_storage_per_wedding, performance_threshold
3. THE System_Config SHALL incluir: google_maps_api_key, mapbox_api_key
4. THE System_Config SHALL incluir: allowed_extensions, blocked_extensions
5. WHEN configuração for alterada, THE System SHALL aplicar imediatamente para novos uploads/operações

### Requirement 22: Dados do Wedding nos Placeholders

**User Story:** Como casal, quero que os dados do meu casamento apareçam automaticamente no site.

#### Acceptance Criteria

1. THE System SHALL substituir placeholder {noivo} com nomes dos usuários com role "couple" no wedding
2. THE System SHALL substituir placeholder {noiva} com nomes dos usuários com role "couple" no wedding
3. THE System SHALL substituir placeholder {data} com wedding_date formatada
4. THE System SHALL substituir placeholder {local} com venue do wedding
5. THE System SHALL substituir placeholder {cidade} com city do wedding
6. IF wedding tiver mais de 2 pessoas no couple, THE System SHALL listar todos os nomes separados por vírgula
7. IF wedding tiver apenas 1 pessoa no couple, THE System SHALL exibir apenas esse nome

### Requirement 23: Notificação de Publicação

**User Story:** Como casal, quero ser notificado quando meu site for publicado com sucesso.

#### Acceptance Criteria

1. WHEN site for publicado com sucesso, THE System SHALL enviar email para todos os usuários couple do wedding
2. THE Email SHALL conter: título do site, URL pública, data/hora da publicação
3. THE Email SHALL usar template básico configurável
4. THE System SHALL registrar envio do email em log
