# Plano — Evento: «Estrutura» operacional, recorrência, previsão e confirmações (RSVP / refeições)

**Objetivo:** corrigir a semântica da aba **Estrutura** no formulário de eventos, separar **recorrência**, modelar **necessidades logísticas** e **modalidade**, prever **lotação**, e desenhar **confirmação de presença** + **confirmação de refeições** (alinhado ao que o evento oferecer).

**Referência de código atual (2026-05):**

- `app/Livewire/Agenda/EventForm.php` — campos `recurrence_*` no componente.
- `resources/views/livewire/agenda/event-form.blade.php` — aba «Estrutura» contém apenas recorrência semanal (placeholder incorreto para o nome da aba).
- `app/Models/Event.php` — já tem `meta` JSON para extensões sem coluna imediata; recorrência já tem colunas dedicadas.

---

## 1. Problema atual

| Aba        | Conteúdo esperado (produto) | Conteúdo atual |
|------------|-----------------------------|----------------|
| Estrutura  | Necessidades operacionais, modalidade, previsão de público | Apenas **recorrência semanal opcional** |
| (n/d)      | Recorrência                 | Misturada na aba errada |

**Decisão de UX:** a palavra **Estrutura** deve refletir **apoio logístico e formato do evento**, não calendário de repetição.

---

## 2. Redesenho das abas (formulário de evento)

Ordem sugerida (ajustar rótulos na UI):

1. **Dados** — título, descrição, tipo de evento (`event_type_id`), datas, estado.
2. **Local** — `location_id`, `meeting_room_id` (coerente com presencial/híbrido).
3. **Público** — cargos / público-alvo (`public_position_ids` + evoluções futuras).
4. **Recorrência** *(nova aba ou bloco dentro de «Dados»)* — apenas:
   - recorrência semanal simples (opcional);
   - «até» (`recurrence_until`);
   - texto de ajuda: alterações em série vs ocorrência única (já previsto no domínio).
5. **Estrutura** *(conteúdo novo)* — ver secção 3.
6. **Responsáveis** — papéis por evento (`EventRoleAssignment` / UI futura).

> **Nota:** Se preferirem menos abas, **Recorrência** pode ser um **painel colapsável** dentro de «Dados», desde que o rótulo «Estrutura» deixe de ser usado para recorrência.

---

## 3. Aba «Estrutura» — requisitos funcionais

### 3.1 Checklist: «O evento vai precisar de…»

Campos booleanos (ou equivalentes), todos opcionais:

| Chave interna (sugestão)   | Rótulo UI (PT)        |
|----------------------------|------------------------|
| `needs_sound_controller`   | Controlador de som   |
| `needs_av`               | Audiovisual          |
| `needs_parking`          | Estacionamento       |
| `needs_meals`            | Refeições (ver 3.2)  |
| `needs_nursing`          | Enfermagem           |
| `needs_valet`            | Manobrista           |
| `needs_other_materials`  | Outros materiais     |

Quando `needs_other_materials` = true, mostrar **campo de texto** obrigatório se a flag estiver ativa:

- *Placeholder exemplo:* «Ex.: escadas, cordas, cinto de segurança, maca…»

### 3.2 Refeições (sub-opções)

Só visíveis se `needs_meals` = true:

- Café da manhã / **Café**
- **Almoço**
- **Lanche**
- **Jantar**

Persistir como booleans ou bitmask em estrutura normalizada (preferível para relatórios e ecrã de confirmação).

### 3.3 Modalidade do evento (formato)

Enum explícito (não confundir com `event_type_id`, que é categoria de negócio):

| Valor interno   | Rótulo UI (PT)                                      |
|-----------------|------------------------------------------------------|
| `online_only`   | Somente online                                       |
| `in_person`     | Presencial                                           |
| `hybrid`        | Híbrido (presencial e participação online)         |

**Regra de UX:** se `online_only`, desativar ou ocultar **Local** / **Sala** (ou torná-los opcionais com aviso). Se `in_person` ou `hybrid`, reforçar validação de local quando a política do produto exigir.

### 3.4 Previsão de pessoas

- Um campo numérico inteiro ≥ 0, opcional: **«Previsão de participantes»** (`expected_attendees` ou nome alinhado ao schema).

Útil para logística (refeições, estacionamento, AV).

---

## 4. Modelagem de dados (arquiteto — fatia 1)

**Opção A — Colunas em `events`:** melhor para filtros, relatórios e políticas.

- `attendance_mode` enum/string (`online_only`, `in_person`, `hybrid`).
- `expected_attendees` unsignedInteger nullable.
- Colunas boolean `needs_*` e quatro booleans `meal_coffee`, `meal_lunch`, `meal_snack`, `meal_dinner` (ou tabela `event_meal_options` se quiserem normalização extrema).
- `other_materials_note` text nullable.

**Opção B — `events.meta` JSON:** MVP rápido; documentar chaves estáveis e migrar para colunas quando houver relatórios.

**Recomendação:** Opção A para campos de produto estáveis (modalidade, refeições, previsão); `meta` para notas experimentais.

**Activity log:** ao adicionar colunas, incluir em `Event::getActivitylogOptions()` o que for relevante para auditoria.

---

## 5. Confirmações (RSVP) — produto e UX

Dois momentos distintos para reduzir erro e melhorar texto:

### 5.1 Ecrã 1 — «Confirmação de participação»

Objetivo: saber se a pessoa **comparece** ao evento (no sentido adequado à modalidade).

**Textos sugeridos (PT):**

- Título: **«Confirmação de participação»**
- Pergunta principal (presencial/híbrido):  
  **«Confirma a sua presença neste evento?»**  
  Opções: *Sim, vou participar presencialmente* · *Não poderei comparecer* · *(opcional)* *Ainda não sei*
- Se o evento for **somente online**:  
  **«Confirma que vai acompanhar o evento online?»**  
  Opções: *Sim* · *Não* · *Talvez*

Estado persistido por utilizador (ou por convidado, quando existir entidade «pessoa»):

- `participation_status`: `confirmed` | `declined` | `tentative` (ajustar enum ao glossário interno).

### 5.2 Ecrã 2 — «Refeições» (condicional)

Mostrar **apenas** se o evento tiver `needs_meals` e pelo menos uma sub-opção (café/almoço/lanche/jantar) ativa.

- Título: **«Refeições no evento»**
- Intro: **«Indique em que refeições prevê participar, para efeitos de logística e cantina.»**  
  (Evitar «vai participar da refeição» solto; explicar o *para quê*.)

Checkboxes **dinâmicos** só para as refeições marcadas na configuração do evento, por exemplo:

- *«Participo no café servido no evento»*
- *«Participo no almoço»*
- *«Participo no lanche»*
- *«Participo no jantar»*

Persistir por refeição (boolean ou registo em `event_meal_rsvps`).

**Política:** só permitir marcar refeição se `participation_status` indicar presença presencial (ou híbrido com opção presencial), salvo regra de negócio explícita para online com kit.

---

## 6. Autorização e escopo

- Quem pode **configurar** estrutura e refeições: mesma policy de `update` do evento (ou permissão granular «gerir logística do evento»).
- Quem pode **responder** RSVP: utilizador convidado / público-alvo (definir na fase «pessoas»); até lá, RSVP pode ficar limitado a utilizadores autenticados com vínculo ao evento.

---

## 7. Ordem de implementação (fatias)

1. **UX + Blade:** renomear/mover recorrência para aba ou bloco «Recorrência»; limpar aba «Estrutura».
2. **Migration + Model + EventService:** campos de estrutura + modalidade + previsão; validação e regras (modalidade vs local).
3. **Livewire:** bind dos novos campos no `EventForm`.
4. **RSVP:** tabela(s) de confirmação + rotas Livewire ou página dedicada + policy.
5. **Notificações / lembretes:** ligar confirmações pendentes a `nexus:event-reminders` ou job específico (fase posterior).

---

## 8. Critérios de aceite (resumo)

- [x] Aba «Estrutura» não contém recorrência.
- [x] Recorrência semanal opcional continua disponível noutro sítio coerente.
- [x] Estrutura grava checklist + nota de materiais + modalidade + previsão.
- [x] Existe fluxo de confirmação de participação com textos claros por modalidade.
- [x] Segundo passo de refeições só aparece quando aplicável e reflete apenas refeições ativas no evento.

**Implementado (2026-05-07):** migração `events` (campos operacionais), `event_rsvps`, `EventForm` / `event-form.blade.php` (abas Recorrência + Estrutura + formato em Dados), `EventRsvpForm` + rota `agenda.events.rsvp`, policy `respond`, links na lista e no editor.

Este ficheiro deve ser lido pelo **orquestrador** antes de partir implementação em PRs pequenos.
