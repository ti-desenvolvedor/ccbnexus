# CCB Nexus — Escopo mestre, arquitetura e plano de execução

**Diretório do projeto:** `C:\www\ccbnexus`  
**Data de referência:** 2026-05-03  
**Papel deste documento:** fonte única de escopo para orquestrador / analista / arquiteto; alinhamento com o que já existe; checklist de implementação por fases.

---

## 1. Objetivo do produto

Sistema modular **CCB Nexus** para: agenda, eventos, secretaria, avisos automáticos, aprovações, relatórios e gestão organizacional, com multi-usuário, multi-local e permissões explícitas.

---

## 2. Stack obrigatória

| Camada        | Tecnologia                          |
|---------------|-------------------------------------|
| Backend       | Laravel 10+                         |
| UI reativa    | Livewire                            |
| Estilo        | TailwindCSS                         |
| Dados         | MySQL                               |
| Cache / filas | Redis                               |

**Padrões:** SOLID, camada de serviço, controllers finos, **Policies/Gates** para autorização, validação em **Form Requests** (ou equivalente na camada HTTP), regras de negócio em **Services** (e **Actions** pequenas quando fizer sentido).

---

## 3. Fluxo de agentes (obrigatório)

Ordem fixa em cada entrega:

1. **Orquestrador** — decompõe trabalho, define ordem e critérios de aceite; **não gera código**.
2. **Arquiteto** — boundaries, modelos, relacionamentos, evolução de schema.
3. **UX/UI** — fluxos, telas, abas (ex.: evento), listagens.
4. **Backend** — migrations, models, services, policies, jobs.
5. **Frontend** — Livewire + views + integração.
6. **Reviewer** — segurança, vazamento de escopo, edge cases.
7. **DevOps** — quando necessário: filas Redis, observabilidade, performance.

**Regra de ouro:** nunca “gerar o sistema inteiro” num único passo; sempre fatias revisáveis e migráveis.

---

## 4. Modelagem alvo (escopo master) vs código atual

### 4.1 O que o prompt master pede (resumo)

- **`locations`**: endereço reutilizável (nome + cidade + UF + CEP + …).
- **Organização:** `regionals` → `administrations` → `churches` (no domínio CCB o nome canônico no repo é **casa de oração** / tabela `prayer_houses`).
- **`rooms`** em `location_id` + **`room_assignments`** (polimórfico: regional, administração, igreja) para salas compartilhadas ou exclusivas.
- **`parkings`** ligados a `location_id`.
- **Users** com vínculo organizacional e opcional OAuth (`google_id`), etc.
- **Permissões:** o master lista tabelas `roles` / `role_user`; no projeto já existe **Spatie Laravel Permission** (`roles`, `permissions`, pivots) — **manter Spatie** como padrão de mercado.
- **Eventos** com tipo, datas, local, sala, status, criador; recorrência; público; papéis; regras de notificação; aprovações; auditoria; pedidos de acesso.

### 4.2 O que já existe no repositório (análise)

| Área                         | Estado atual | Observação |
|-----------------------------|--------------|------------|
| Auth / perfil               | Rotas `dashboard`, `profile`, `auth.php` | Base Laravel Breeze/Jetstream-like |
| RBAC                        | `RolesAndPermissionsSeeder`, `config/permission.php`, migration Spatie | Alinhado a “boas práticas”; não duplicar modelo `role_user` manual |
| Organização                 | `regionals`, `administrations`, `prayer_houses` | **Sem** `location_id` nas tabelas organizacionais |
| Endereço                    | `addresses` **morph** (`addressable`) para Administração / Casa de oração | Endereço “embutido” no dono, não entidade `locations` compartilhada |
| Salas                       | `meeting_rooms` com `owner` **morph** (Administração \| Casa) | Compartilhamento entre níveis **não** está no `room_assignments` do master |
| Reservas                    | `room_reservations` com status, aprovação, notas | Bom núcleo para conflitos e fluxo de aprovação |
| Referência eventos / avisos | `event_types`, `event_role_templates`, `notification_rule_templates` | Templates, ainda sem `events` / ocorrências |
| Auditoria                   | `activity_log` (Spatie Activity Log) | Cobre parte do pedido de `audit_logs` |
| Usuário                     | Tabela `users` mínima (name, email, password) | **Faltam** `phone`, `google_id`, FKs organizacionais, `is_super_admin` |
| Agent memory (extra)        | `agent_memory_entries` + comando | Fora do master; pode coexistir |

### 4.3 Lacunas principais (delta escopo → código)

1. Entidade **`locations`** (ou renomear mentalmente para “Local físico”) e FKs `location_id` em regionais / administrações / casas / salas / estacionamentos — hoje o endereço é **morph** granular, não reutilização explícita por ID.
2. **`rooms` + `room_assignments`** vs `meeting_rooms` + `owner` morph — precisa **estratégia de evolução** (ver secção 5).
3. **`events`**, **`event_recurrences`**, **`event_occurrences`**, **`audiences`**, **`event_roles`**, **`approvals`**, **`access_requests`**, **`parkings`** — **não** migrados ainda.
4. UI CRUD: não há módulos Livewire de manutenção para organização / salas / eventos (só dashboard exemplo).
5. Escopo de dados por **contexto ativo** (regional → admin → casa) — descrito em `AGENTS.md`; falta implementação transversal (middleware + sessão + policies).
6. **`audiences` plano** não cobre **grupo → subgrupo → departamento (regional vs local) → cargo**; a taxonomia detalhada de **Públicos** está em `AGENTS.md` (secção *Públicos, grupos, departamentos e cargos*); implementação em fatias com pivôs para eventos e, depois, pessoas (N:N).

### 4.4 Públicos — resumo para arquitetura

- **Grupo** e **subgrupo** (opcional) organizam o catálogo; **cargo** é a **folha** usada na aba Público do evento (com atalhos por agregação).
- **Departamentos** com âmbito **regional** vs **local** (administração / casa de oração), alinhados à hierarquia organizacional já adotada no produto.
- **Cargos** pertencem a **departamentos**; catálogo versionado por escopo com **Policy** e contexto ativo.
- **Colaboradores da administração** que compõem a operação da **regional** incluem, entre outros, **coordenadores de departamento** (articulação por departamento no âmbito regional ou na administração); no produto: flag `is_department_coordinator` no cargo (ver `AGENTS.md`).
- **Pessoas** com múltiplas filiações: fora do primeiro CRUD, mas o schema deve permitir **vários** vínculos pessoa ↔ cargo.
- Fonte normativa de negócio: `AGENTS.md` (mesmo tópico).

---

## 5. Arquitetura recomendada (decisões antes de codar)

### 5.1 Endereços e “locations”

**Opção A (alinhada ao master):** criar `locations` com campos normalizados; `regionals`, `administrations`, `prayer_houses` passam a ter `location_id` opcional; migração de dados a partir de `addresses` existentes (script one-shot ou migration com dados).

**Opção B (mínima):** manter `addresses` morph e introduzir `locations` apenas para salas/estacionamentos compartilhados.

**Recomendação de mercado:** **Opção A** a médio prazo — deduplica endereço, simplifica relatórios geográficos e salas por prédio compartilhado. Executar em **migração dedicada** após backup e com revisão de dados.

### 5.2 Salas compartilhadas

**Opção A:** renomear conceitualmente `meeting_rooms` → `rooms`, adicionar `location_id`, tabela `room_assignments` (morph) e deprecar `owner` morph gradualmente.

**Opção B:** manter `meeting_rooms.owner` e adicionar `room_assignments` como **fonte da verdade** para “quem pode reservar”, com `owner` nullable quando a sala for 100% por local.

**Recomendação:** **Opção A** se o produto priorizar “sala no edifício X usada por N administrações”; **Opção B** se quiser menor churn no schema já criado. O checklist abaixo prevê **decisão explícita na Fase 2.0** antes de escrever migrations novas.

### 5.3 Permissões

Manter **Spatie**; mapear permissões do master para `Permission::firstOrCreate` com nomes estáveis (já iniciado no seeder). **Model_has_roles** com equipe por escopo: avaliar `teams` do Spatie ou tabela pivot custom `model_has_roles` + `regional_id` / `administration_id` / `prayer_house_id` (decisão na Fase 1.5).

### 5.4 Auditoria e aprovações

- Auditoria: **Spatie Activitylog** já presente — padronizar `logs_activity` nos models sensíveis e eventos de domínio.
- Aprovações: tabela polimórfica `approvals` (approvable_type/id, estado, justificativa, actor) reutilizável para eventos e alterações de evento.

### 5.5 Avisos (30/15/7/1 dias)

- `notification_rules` (ou templates + instâncias por evento) + **Jobs agendados** (Redis queue) + idempotência por `(rule_id, occurrence_id, offset)`.

---

## 6. Módulos (entregáveis de produto)

| Módulo        | Conteúdo principal |
|---------------|--------------------|
| Acesso        | Login, registro/solicitação, `access_requests`, aprovação |
| Usuários      | CRUD, perfil, escopo, papéis Spatie |
| Organização   | Regionais, administrações, casas de oração, locations |
| Infra         | Salas, reservas, estacionamentos |
| Agenda/Eventos| Calendário, CRUD, recorrência, ocorrências |
| Públicos/Papéis | Taxonomia **grupo / subgrupo / departamento (âmbito) / cargo** + pivôs em eventos; `event_roles` e atribuições; evolução ou substituição de `audiences` plano (ver `AGENTS.md`) |
| Avisos        | Regras e disparo automático |
| Aprovações    | Workflow e justificativas |
| Secretaria    | fluxos operacionais (depende do negócio detalhado) |
| Relatórios    | exports, filtros por escopo |
| Auditoria     | trilha + consulta para moderador |

---

## 7. Regras de negócio (checklist de conformidade)

- [ ] Evento pode ser recorrente; edição pode ser **série** ou **ocorrência única**.
- [ ] Cancelamento e alterações sensíveis exigem **justificativa** e registro em auditoria/aprovação.
- [ ] Localização: evitar duplicidade (normalização / `locations` ou dedup na importação).
- [ ] Sala: conflito de horário **impossível** na mesma sala (transação + índice/constraint de aplicação).
- [ ] Reserva/evento: respeitar **permissão + escopo + estado**.
- [ ] Avisos: offsets 30, 15, 7, 1 dia (configurável por tipo de evento).
- [ ] Público-alvo de eventos: seleção coerente com **catálogo por escopo** (regional / admin / casa); não vazar cargos de outra regional.
- [ ] Separação clara: **permissões Spatie** ≠ **cargos de domínio** (estrutura colaborativa / público).
- [ ] Nenhuma regra de negócio pesada em **controllers**; apenas orquestração HTTP.

---

## 8. UX — cadastro de evento (abas)

1. Dados principais  
2. Local  
3. Público  
4. **Recorrência** (semanal simples, opcional) — *hoje estava na aba «Estrutura» no UI; deve ficar numa aba ou bloco próprio*  
5. **Estrutura** — necessidades operacionais (som, AV, estacionamento, refeições, enfermagem, manobrista, outros materiais), **modalidade** (online / presencial / híbrido), **previsão de participantes**; ver plano detalhado em [`docs/EVENT_OPERATIONS_AND_RSVP_PLAN.md`](EVENT_OPERATIONS_AND_RSVP_PLAN.md)  
6. Responsáveis  

**Pós-cadastro / participante:** confirmação de **presença/participação** e, em segundo passo, **refeições** conforme o configurado no evento — descrito no mesmo documento.

Cada módulo de manutenção (organização, salas, etc.) deve ter **lista**, **criação** e **edição** com padrões: validação visível, estados vazios, confirmação destrutiva, paginação, busca, autorização por policy.

---

## 9. Processos de mercado (sugestão explícita)

| Prática | Aplicação no CCB Nexus |
|---------|-------------------------|
| Form Requests | Validação de entrada por rota/ação |
| Policies | `view`, `create`, `update`, `delete`, `approve`, `cancel` por model + escopo |
| Services + DTOs | Criação de evento, split de ocorrência, reserva com overlap check |
| Events + listeners | Auditoria, notificações secundárias |
| Jobs + Redis | Avisos agendados, relatórios pesados |
| Database transactions | Reservas e aprovações |
| Feature tests | Overlap, escopo entre regionais, fluxo de aprovação |
| API interna coesa | Evitar lógica duplicada Livewire vs futura API |

---

## 10. Ordem de implementação (fases)

### Fase 1 — Acesso, usuários, papéis

- Completar perfil de usuário (telefone, OAuth se necessário).
- Modelagem de **escopo** + integração Spatie (teams ou pivot com escopo).
- Policies base e middleware de **contexto ativo**.
- Seeds: admin dev + roles (já iniciado).

### Fase 2 — Organização + locations + salas

- Decisão schema salas (secção 5.2) + migrations.
- `locations` + migração a partir de `addresses` se Opção A.
- CRUD Livewire: regionais, administrações, casas, endereços/locations, salas.
- Ajustar `room_reservations` ao novo modelo de sala se mudar.

### Fase 3 — Eventos

- Tabelas `events`, recorrência, ocorrências, **público-alvo** (pivôs com taxonomia grupo/subgrupo/departamento/cargo conforme `AGENTS.md`), papéis de evento.
- UI com abas do master; aba **Público** alinhada ao catálogo hierárquico (não apenas lista plana de audiências).
- Integração com salas e locations.
- **Estrutura operacional + RSVP / refeições:** plano em [`docs/EVENT_OPERATIONS_AND_RSVP_PLAN.md`](EVENT_OPERATIONS_AND_RSVP_PLAN.md) (separar recorrência da aba «Estrutura», modalidade, previsão de público, confirmações).

### Fase 4 — Avisos

- Regras, templates, jobs, logs de envio.

### Fase 5 — Aprovações

- `approvals` polimórfico; UI de fila para aprovador.

### Fase 6 — Relatórios e secretaria

- Exports (CSV/PDF conforme necessidade), dashboards filtrados por escopo.

---

## 11. Checklist de implementação (operacional)

### Pré-requisitos

- [ ] Backup de BD antes de migrations destrutivas/rename.
- [ ] `.env` com Redis para filas em desenvolvimento (paridade com produção).

### Fase 1 (detalhada)

- [ ] Migration: colunas em `users` (`phone`, `google_id` nullable, flags super admin, FKs de contexto padrão — definição final na story).
- [ ] Serviço `UserProvisioningService` (ou nome alinhado ao projeto) para criação/atribuição de papel.
- [ ] `UserPolicy` + testes de autorização.
- [ ] UI: edição de perfil estendida (campos novos).

### Fase 2 (detalhada)

- [ ] ADR ou nota no PR: escolha Opção A/B para salas.
- [ ] Migrations `locations`, `parkings`, `room_assignments` (se aplicável).
- [ ] Services: `LocationService`, `RoomService`, `ReservationService` (overlap).
- [ ] Livewire: `Regionals\Index|Create|Edit`, idem para Administrações, Casas, Salas, Reservas.
- [ ] Policies por entidade com escopo regional.

### Fase 3+

- [ ] Domain events + testes de recorrência.
- [ ] Fila de notificações + testes de offsets.
- [ ] Relatórios e permissões de exportação.

### Revisão (cada PR)

- [ ] Reviewer: vazamento de escopo entre regionais.
- [ ] Reviewer: transações e race conditions em reservas.

---

## 12. Texto de comando inicial (referência)

> Crie o módulo de autenticação completo seguindo o padrão definido.

No estado atual, a autenticação base existe; o próximo comando útil será por fase, por exemplo:

> Implemente a Fase 2.0 (ADR salas + `locations`) com migrations e CRUD mínimo Livewire para regionais e casas de oração.

---

## 13. Resultado esperado do programa

Sistema **modular**, **escalável**, **multi-usuário**, **multi-local**, com separação clara de camadas e governança (aprovações, auditoria, permissões por escopo).

---

## 14. Governança deste documento

- Alterações de escopo que afetem **agenda/eventos/reservas** devem registrar **justificativa** no PR/commit message.
- Este ficheiro é a **linha de base**; desvios devem ser explícitos (ADR ou secção “Decisões” atualizada).
