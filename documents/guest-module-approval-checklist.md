# Checklist de Aprovação — Módulo de Convidados/RSVP

Data: 2026-02-12

## 1) Escopo da 1ª Entrega (MVP)
Marque o que entra na **primeira versão**:
- [x] Cadastro manual de convidados e households
- [x] RSVP básico (Confirmado/Recusado/Talvez/Sem resposta)
- [x] RSVP por evento (cerimônia/festa/jantar)
- [x] Formulários dinâmicos (perguntas customizadas)
- [x] Importação CSV/Excel
- [x] Deduplicação inteligente
- [x] Link mágico com token
- [x] Modo restrito (somente convidados)
- [x] Check-in por QR
- [x] Check-in manual por busca
- [x] Logs de check-in (operador, horário, método)
- [x] Dashboard básico de status
- [x] Exportação CSV

## 2) Regras de Acesso
- [x] **Padrão aberto** (qualquer convidado pode responder)
- [x] **Padrão restrito** (apenas convidados cadastrados)
- [x] Restrição configurável por casamento

## 3) Convites
- [x] Token único por household
- [x] Expiração do link (definir dias): _______
- [x] Reemissão permitida

## 4) Canais de Comunicação
- [x] E-mail (ativo já no MVP)
- [x] WhatsApp (ativo já no MVP)
- [x] SMS (ativo já no MVP)
- [x] Apenas preparado, ativação futura

## 5) Check-in
- [x] QR obrigatório
- [ ] Modo offline (cache local) obrigatório
- [ ] Offline pode ficar v2

## 6) LGPD e Privacidade
- [x] Registrar consentimento
- [x] Mascara dados para Staff/Recepção
- [x] Auditoria obrigatória para ações críticas

## 7) Integração com o Site e App
- [x] RSVP no site deve estar integrado na 1ª entrega
- [x] Preparar vínculo futuro com App (guest como usuário)

## 8) Decisões Técnicas
- URL pública do RSVP:
  - [x] `/site/{slug}/rsvp`
  - [ ] `/convite/{token}`
  - [ ] Outra: ___________________________

- `custom_domain` deve ser único globalmente?
  - [x] Sim
  - [ ] Não

## 9) Critérios de Aceitação (prioridade alta)
- [x] RSVP em < 45s no celular
- [x] Importar 500 convidados em < 2 min
- [x] Check-in QR < 3s

---

### Observações / Ajustes



---

### Aprovação
- Responsável: ________________________
- Data: ____ / ____ / ______
