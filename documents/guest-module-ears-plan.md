# Plano Técnico + Backlog — Módulo de Convidados/RSVP (SPEC EARS)

Data: 2026-02-12  
Status: Proposta técnica para implementação futura  
Escopo: Implementação **completa** conforme SPEC (EARS), com múltiplos canais preparados (e‑mail/WhatsApp/SMS) e modo **aberto** por padrão.

---

## 1) Objetivo
Implementar módulo completo de convidados e RSVP com experiência best‑in‑class, automações, check‑in e relatórios. O módulo será integrado ao Editor de Sites (seção Convidados/RSVP) e deve evoluir para integração futura com o App do Casamento (convidado como usuário).

---

## 2) Diagnóstico do Sistema Atual (levantamento rápido)

### 2.1 O que já existe
- **Seção RSVP no site**: apenas placeholder/estático no conteúdo do site.
  - Referência: `resources/views/public/site.blade.php` e templates em `database/seeders/SiteTemplateSeeder.php`.
- **Papel “guest” no sistema**:
  - `users.role` e `wedding_user.role` já suportam `guest`.
- **Rotas “guests”**: existem no `routes/api.php`, porém sem implementação relevante.
- **Convites internos (casal/organizador)**: `PartnerInvite` existe, mas não é o convite de convidados do casamento.
- **RSVP em APP**: há um placeholder em `routes/api.php` para `POST /api/app/rsvp`.

### 2.2 Lacunas principais
- Não há modelos/tabelas de **convidado** e **núcleo (household)**.
- Não há **RSVP real**, nem formulários dinâmicos.
- Não há **check‑in**, **QR**, **logs**, **painéis**, **importação CSV**, **comunicação**.
- Editor de Sites apenas exibe seção; não integra com dados reais.

---

## 3) Diretrizes de Implementação (completo)

### 3.1 Conceitos centrais
- **Household** (núcleo/grupo familiar) é a unidade principal de RSVP e convite.
- Convidados podem ser:
  - responsável (head), dependente, acompanhante (plus‑one).
- RSVP pode ser por **evento** (cerimônia/festa/jantar etc.), com perguntas dinâmicas por evento.
- Convidado acessa por **link mágico** (token) com modo **aberto** ou **restrito**.

### 3.2 Segurança e LGPD
- Consentimento e base legal registrados.
- Mascara dados para perfis de staff.
- Auditoria completa para ações críticas.

---

## 4) Proposta de Modelagem (alto nível)

### Tabelas sugeridas
- `guest_households` (núcleos)
  - `id`, `wedding_id`, `name`, `code`, `quota_adults`, `quota_children`, `plus_one_allowed`, `tags`, `side`, `priority`

- `guests`
  - `id`, `wedding_id`, `household_id`, `user_id` (futuro), `name`, `email`, `phone`, `nickname`, `role_in_household`, `is_child`, `category`, `side`, `status`, `notes`

- `guest_events`
  - `id`, `wedding_id`, `name`, `date`, `is_active`, `rules` (ex.: depende de outro evento)

- `guest_rsvp`
  - `id`, `guest_id`, `event_id`, `status`, `responses_json`, `updated_by`, `updated_at`

- `guest_invites`
  - `id`, `household_id`, `guest_id` (opcional), `token`, `channel`, `expires_at`, `used_at`, `created_by`

- `guest_checkins`
  - `id`, `guest_id`, `event_id`, `checked_in_at`, `operator_id`, `method` (qr/manual), `device_id`

- `guest_messages`
  - `id`, `wedding_id`, `channel`, `template_id`, `payload`, `sent_at`

- `guest_message_logs`
  - `id`, `guest_id`, `message_id`, `status` (sent/delivered/clicked)

- `guest_audit_logs`
  - `id`, `wedding_id`, `actor_id`, `action`, `context_json`

---

## 5) Integração com Editor de Sites
- Seção “RSVP” deve consumir dados reais (eventos/estado do convidado).
- No modo **aberto**, convidado pode iniciar sem token, mas ainda será criado em `guest_households/guests`.
- No modo **restrito**, exige token ou identificação leve.

---

## 6) Fases de Implementação (completo)

### Fase 1 — Core de Dados + CRUD + RSVP Básico
- Modelos + migrations + API CRUD
- Household + Guests + RSVP básico
- RSVP aberto (sem token obrigatório)
- Editor: link do RSVP real

### Fase 2 — Importação + Dedupe + Segmentação
- Import CSV/Excel com pré‑visualização
- Dedupe inteligente
- Tags, grupos, lados, categorias

### Fase 3 — Convites + Link Mágico + Restrições
- Tokens por household
- Modo restrito + reemissão
- Logs e expiração

### Fase 4 — Formulários Dinâmicos
- Perguntas configuráveis por evento
- Obrigatoriedade por evento
- Histórico de edição

### Fase 5 — Comunicação Omnichannel
- Preparar integração com Email/WhatsApp/SMS
- Templates com variáveis
- Logs de entrega e interação

### Fase 6 — Check‑in + QR + Operação
- QR por convidado/household
- Tela de check‑in (web)
- Log por operador + método
- (Opcional) modo offline

### Fase 7 — Dashboard + Relatórios
- Totais por status e segmentação
- Export CSV/PDF
- Insights (previsão de presença)

---

## 7) Backlog (Épicos e Stories)

### EPIC A — Modelagem e Base do Módulo
1. Criar migrations para `guest_households`, `guests`, `guest_events`, `guest_rsvp`.
2. Criar modelos Eloquent e relacionamentos.
3. Adicionar seeds básicos (eventos padrão: cerimônia/festa).

### EPIC B — API e Segurança
1. CRUD de households e guests.
2. CRUD de eventos RSVP.
3. Endpoint de RSVP (aberto) e validação mínima.
4. Políticas de acesso por papel.

### EPIC C — Importação e Dedupe
1. Upload e mapeamento de CSV.
2. Tela de pré‑visualização com validações.
3. Dedupe por nome + contato.

### EPIC D — Convites e Acesso
1. Gerar tokens e links mágicos.
2. Implementar modo restrito.
3. Expiração e reemissão de convite.

### EPIC E — Formulários Dinâmicos
1. Editor de perguntas por evento.
2. Armazenamento das respostas em JSON.
3. Regras condicionais (opcional).

### EPIC F — Comunicação Omnichannel
1. Templates com variáveis (nome, evento, link RSVP).
2. Logs de envio e interação.
3. Integração de canal (placeholder) + ativação posterior.

### EPIC G — Check‑in e Operação
1. QR por household/guest.
2. Tela de check‑in web (busca rápida).
3. Registro de check‑in com operador.
4. (Opcional) modo offline.

### EPIC H — Dashboard & Relatórios
1. Totais por status e segmentação.
2. Exportações CSV/PDF.
3. Insights (previsão de presença).

---

## 8) Estimativa (implementação completa)

> **Estimativa total**: 5–9 semanas (1 dev), podendo reduzir com priorização e corte de escopo opcional.

### Por fase (estimativa média)
- Fase 1: 1–2 semanas
- Fase 2: 1–2 semanas
- Fase 3: 1 semana
- Fase 4: 1–2 semanas
- Fase 5: 1–2 semanas (preparar integrações)
- Fase 6: 1–2 semanas
- Fase 7: 1 semana

---

## 9) Pontos de Decisão (para você validar)
1. `custom_domain` único por site (global)?
2. RSVP por evento obrigatório no MVP?
3. Modo offline é requisito obrigatório ou pode ficar para v2?
4. Integrações de WhatsApp/SMS serão ativadas quando?

---

## 10) Próximos Passos
1. Validar decisões do item 9.
2. Escolher MVP “completo” (quais fases entram na 1ª entrega).
3. Definir ordem das integrações (e‑mail → WhatsApp → SMS).

