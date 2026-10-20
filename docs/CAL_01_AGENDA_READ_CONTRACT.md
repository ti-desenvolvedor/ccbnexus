# CAL-01 — Contrato de leitura da agenda

**Estado:** fechado (arquiteto)  
**Data:** 2026-10-16  
**Implementação:** CAL-03. Sem UI, sem migration, sem expansão virtual de recorrência.

## Decisão

Um único objeto de leitura devolve os eventos do intervalo. A lista de séries, a grelha, a agenda e o PDF chamam esse objeto com o mesmo critério. A leitura não grava, não sincroniza recorrência e não recalcula datas.

Classes (CAL-03):

| Tipo | Nome |
|------|------|
| Critério | `App\Services\Agenda\AgendaReadCriteria` |
| Consulta | `App\Services\Agenda\AgendaReadQuery` |
| Resultado | `App\Services\Agenda\AgendaReadResult` |

`AgendaReadQuery::read(User $user, AgendaReadCriteria $criteria): AgendaReadResult`

Quem chama autoriza `viewAny` em `Event` antes. A query repete o escopo na SQL para não depender do caller.

## Fonte das linhas

Ocorrências já são linhas em `events` (`is_occurrence = true`, `parent_event_id` preenchido), copiadas por `EventRecurrenceService`. A data do pai é a primeira ocorrência; os filhos são as seguintes.

A leitura **não** chama o planner. Série sem filhos sincronizados aparece só na data do pai.

## Modos

| `mode` | O que entra | Janela |
|--------|-------------|--------|
| `occurrences` | Pai e filhos cuja data cruza a janela | Obrigatória |
| `series` | `is_occurrence = false` e `parent_event_id` nulo | Ignorada |

`occurrences` alimenta calendário semanal, grelha mensal, agenda e PDF. `series` mantém a lista operacional atual.

## Janela

Fuso: `config('app.timezone')`.

| `window` | Início inclusivo | Fim exclusivo |
|----------|------------------|---------------|
| `week` | segunda 00:00 (`startOfWeek()`, igual a `RoomCalendar`) | segunda seguinte 00:00 |
| `month` | dia 1 00:00 | dia 1 do mês seguinte 00:00 |

Cruzamento (igual às reservas): `starts_at < fim` e `ends_at > início`. `ends_at` nulo trata-se como `starts_at`.

Intervalo máximo: 62 dias. Fora disso a query rejeita o critério. PDF só aceita `window = month` e `mode = occurrences`. Ano tipo Excel continua fora (AGD-01).

## Estado efetivo

`cancel`, `approve` e `reject` em `EventService` alteram só a linha recebida. O filho pode ficar com `status` copiado e desatualizado. A leitura não usa o `status` do filho como verdade da série.

Estado efetivo:

- sem `parent_event_id`: o `status` da própria linha
- com pai: o `status` do pai
- exceção: se a própria linha está `cancelled` ou `rejected`, esse valor ganha (reserva para cancelar uma ocorrência sem apagar a série)

Conjunto visível:

| Estado efetivo | Quem vê |
|----------------|---------|
| `published`, `pending_approval` | quem passa `viewAny` e o escopo |
| `draft` | o mesmo, e ainda `editar_evento` ou super-admin |
| `cancelled`, `rejected` | ninguém nesta leitura |

Filtro na SQL, não em memória: a linha passa se o estado efetivo está no conjunto. `draft` não entra para quem só tem `visualizar_evento`.

## Escopo

Ordem, alinhada a `EventList` e a `EventPolicy::view`:

1. Sem `viewAny` → vazio e 403 no caller (a query não devolve linhas).
2. Quem não é super-admin: `regional_id` em `accessibleRegionalIds()` ou `regional_id` nulo.
3. Se `OrganizationalContextService::activeRegionalId()` está definido: `regional_id` igual a esse id. Evento sem regional sai.
4. Super-admin sem contexto ativo: todas as regionais (a tela mostra o aviso de contexto vazio).

PDF é ação sensível. Se o contexto ativo não for uma única regional, `AgendaReadQuery` recusa com exceção de domínio `AgendaRegionalContextRequired`. Não gera PDF multi-regional nem PDF de eventos com `regional_id` nulo. A tela, nesse caso, continua a listar o que as regras 2–3 permitem.

Administração e casa de oração não filtram evento: o modelo não tem essas FKs. Não inventar filtro.

## Filtros partilhados

Iguais nos dois modos e no PDF. Vazio significa «não filtrar».

| Campo | Regra |
|-------|--------|
| `search` | `title LIKE %termo%` (igual à lista atual; não pesquisa descrição) |
| `eventTypeId` | `event_type_id` exacto |
| `channel` | `bulletin`, `whatsapp`, `bulletin_only`, `whatsapp_only` — mesmos predicados de `EventList` |

## Ordenação, página e tecto

| Modo | Ordem | Entrega |
|------|--------|---------|
| `occurrences` | `starts_at` ASC, `id` ASC | coleção, máximo 500 linhas |
| `series` | `starts_at` DESC, `id` DESC | paginação (`UsesAccountPagination`) |

Acima de 500 ocorrências no intervalo: devolver as 500 primeiras e `truncated = true`. A UI (CAL-05/06) mostra que o recorte está incompleto. Não alargar o tecto no PDF.

## Eager load

Só o que a lista e o PDF precisam, para não haver N+1:

- `regional:id,name`
- `eventType:id,name`
- `location:id,name,city`
- `meetingRoom:id,name`
- `roleAssignments.roleTemplate:id,slug,name`
- `roleAssignments.user:id,name`

O rótulo de quem atende continua em `Event::attendantLabel()` depois deste load.

## Resultado

`AgendaReadResult` expõe:

- `items` (coleção ou paginator)
- `mode`, `window`, `rangeStart`, `rangeEnd`
- `regionalId` (contexto aplicado, ou `null`)
- `includesDrafts` (bool)
- `truncated` (bool)

O PDF recebe este resultado. Não faz uma segunda query com outros filtros.

## Fora deste contrato

- Schema novo, cascade de `status` para filhos, planner na leitura.
- CSV de `EventExportService` (permanece em `visualizar_relatorios` e na query antiga).
- Grelha anual.

## Testes que fecham CAL-01 (na implementação)

- Regional A não vê evento da regional B, nem com `regional_id` nulo quando o contexto é A.
- `cancelled` e `rejected` ficam de fora; filho `published` com pai `cancelled` também.
- `draft` só com `editar_evento` (ou super-admin); `visualizar_evento` sozinho não vê.
- Pai `published` com filho ainda `draft` aparece no intervalo do filho.
- Semana e mês respeitam o cruzamento de `starts_at` / `ends_at`.
- PDF sem regional ativa única levanta `AgendaRegionalContextRequired`.
- Dois modos com o mesmo critério de filtro não divergem no predicado de canal, tipo e pesquisa.
