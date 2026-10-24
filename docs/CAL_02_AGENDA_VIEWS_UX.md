# CAL-02 — Vistas da agenda (UX)

**Estado:** fechado (UX)  
**Data:** 2026-10-16  
**Contrato de dados:** `docs/CAL_01_AGENDA_READ_CONTRACT.md`  
**Implementação visual:** CAL-04, CAL-05, CAL-06. Este documento não altera PHP de domínio.

## Objetivo da tela

A página já existente `/agenda/events` passa a ser o único lugar das quatro vistas. O cabeçalho continua «Agenda / Eventos» e o botão «Novo evento» (só com `create`). O contexto ativo permanece no topbar; a página repete o nome da regional para a pessoa saber o que está a ver antes de gerar PDF.

O seletor de vista não cria rotas novas. O estado fica na query da página para sobreviver a refresh e a «abrir noutro separador»:

| Parâmetro | Valores | Omissão |
|-----------|---------|---------|
| `view` | `week`, `list`, `month`, `agenda` | `month` |
| `list` | `occurrences`, `series` | `occurrences` (só vale com `view=list`) |
| `cursor` | `YYYY-MM-DD` | hoje |

Filtros de pesquisa, tipo e canal mantêm-se ao mudar de vista e ao andar no tempo. São os mesmos campos do CAL-01 e os mesmos que o PDF recebe.

## Hierarquia de campos / abas

Ordem vertical, de cima para baixo:

1. **Contexto da leitura** — uma linha: «Contexto: {nome da regional}». Se `regionalId` é nulo: faixa âmbar «Selecione uma regional no topo para gerar o PDF. Esta tela mostra todas as regionais a que tem acesso.» Se `includesDrafts`: texto curto «Inclui rascunhos» ao lado do contexto.
2. **Vistas** — `role="tablist"`, quatro separadores com texto (não só ícone), `aria-selected`, alvo mínimo 44px: **Calendário**, **Lista**, **Mensal**, **Agenda**.
3. **Submodo da lista** — só em Lista: dois botões **Ocorrências** e **Séries**. Séries é a tabela operacional atual (`mode=series`). Ocorrências é a cronologia do mês (`mode=occurrences`, `window=month`).
4. **Tempo** — «Anterior», «Hoje», «Seguinte» e o rótulo no centro. Calendário: semana («12–18 out 2026»), semana ISO de segunda a domingo. Mensal, Agenda e Lista em Ocorrências: «outubro 2026». Em Séries, em qualquer vista, o bloco de tempo **não aparece** (a janela é ignorada pelo contrato).
5. **Filtros** — pesquisa; tipo, situação e canal em `x-nexus.searchable-select` (mostra as primeiras opções e filtra ao digitar). Empilham em coluna até `lg`.
6. **PDF** — botão secundário «Gerar PDF», só em Mensal e Agenda. Nas outras vistas não fica desativado à mostra: simplesmente não está lá.
7. **Corpo** da vista activa.

Mapeamento vista → critério (quem implementa não inventa outro):

| Vista | `mode` | `window` |
|-------|--------|----------|
| Calendário | `occurrences` | `week` |
| Lista / Ocorrências | `occurrences` | `month` |
| Lista / Séries | `series` | (nenhuma) |
| Mensal | `occurrences` | `month` |
| Agenda | `occurrences` | `month` |

### Calendário (semana)

Grelha hora × dia, 07:00–21:00, coluna da hora sticky, igual à leitura do calendário de salas. Não é grelha de reserva: célula vazia fica vazia, sem botão «Livre». O evento é um bloco com hora e título, a atravessar as horas que ocupa. Cores só de estado: publicado sem badge extra; pendente em âmbar «Pendente»; rascunho em cinza «Rascunho». Mais do que cabe na célula: o título trunca. Clique com `update` abre a edição; ocorrência abre o pai (o formulário já redirecciona). Sem `update`, o bloco não é link.

### Lista

- **Ocorrências:** tabela a partir de `md`; abaixo de `md`, cartões. Colunas: data e hora, título, tipo, local (sala, senão local, senão «—»), quem atende, canais (Boletim / WA, como hoje), estado, ações. Ações: «Confirmar» com `respond`, «Editar» com `update`. Paginação não se aplica (coleção do mês).
- **Séries:** a tabela actual, com paginação. «Editar» e «Confirmar» mantêm-se.

### Mensal

Sete colunas, segunda a domingo, semanas do mês civil. Dia de hoje com realce. Dias do mês vizinho esbatidos e sem eventos. Em cada dia, no máximo três linhas «HH:mm Título». Se houver mais, a quarta linha é «+N» e muda para a vista Agenda no mesmo `cursor` (não abre um popover). Clique na linha com `update` abre a edição (pai, se for ocorrência).

### Agenda

Lista vertical do mês, um bloco por dia que tem eventos. Dias vazios não ocupam página. Cabeçalho do dia: «segunda, 13 de outubro». Cada evento: intervalo de horas, título, tipo, local ou sala, quem atende, badges de canal e, se não for publicado, o badge de estado. É a vista feita para ler e para o PDF em retrato.

### PDF

O botão é um link `target="_blank"` (o mesmo gesto do PDF da lista de batismos), com os filtros e o `cursor` actuais.

| Vista de origem | Papel | Miolo |
|-----------------|-------|--------|
| Mensal | paisagem | grelha do mês, os mesmos três eventos por dia e «+N» |
| Agenda | retrato | os mesmos blocos dia a dia |

Cabeçalho do ficheiro: nome da regional, mês por extenso, e a nota «Inclui rascunhos» quando `includesDrafts`. Se `truncated`, a primeira linha do PDF e uma faixa na tela dizem «Primeiras 500 ocorrências. O período está incompleto.» O PDF não pede segunda confirmação (não altera dados).

Sem regional única, o botão fica desativado, `aria-disabled`, e o texto da faixa âmbar explica o motivo. Não se dispara o pedido que o contrato recusa com `AgendaRegionalContextRequired`.

## Estados (vazio, loading, erro, sucesso)

- **Vazio no período** (ocorrências): `x-nexus.empty-state`, título «Nenhum evento neste período», descrição «Ajuste a pesquisa, o tipo ou o canal, ou mude o mês.» CTA «Novo evento» só com `create`.
- **Vazio de séries:** título «Nenhuma série neste contexto», descrição a apontar o seletor de regional no topo. CTA «Novo evento» com `create`.
- **Truncado:** faixa por cima do corpo, não substitui a lista. Some quando o resultado volta com `truncated = false`.
- **A carregar:** a zona do corpo baixa de opacidade com `wire:loading`. Separadores, filtros e PDF permanecem utilizáveis. Não há toast de sucesso na leitura.
- **Período rejeitado pela query:** alerta inline «Período inválido.» e o cursor volta a hoje.
- **PDF aberto sem regional** (separador antigo): página curta «Selecione uma regional no topo e volte a gerar o PDF.» Sem stack trace.
- **403:** a página de eventos nem chega a montar (já é `viewAny`).

Cancelados e rejeitados não têm estado visual: o contrato não os devolve.

## Breakpoints / mobile

- Separadores: fila com scroll horizontal, cada um `min-h-[44px]`, sem esconder o rótulo.
- Filtros: `flex-col` até `lg:flex-row`.
- Calendário e grelha mensal: `overflow-x-auto`; a coluna da hora e o número do dia não desaparecem.
- Agenda: uma coluna; o cabeçalho do dia pode ficar sticky.
- Lista de ocorrências: cartões abaixo de `md`, tabela daí para cima. Séries seguem a mesma troca.
- «Gerar PDF» em ecrã estreito fica por baixo dos separadores, largura total, 44px.

## Atalhos e Facilitador

Não há atalho global novo. `g e` e a paleta (`Ctrl+K`) continuam a abrir Eventos. Anterior/seguinte são botões, não teclas, para não competir com a pesquisa.

Não se acrescenta item de menu. Pendente de aprovação lê-se no badge âmbar dentro da vista em que a pessoa já está. Nada nesta tela aprova, rejeita ou cancela: não há campo de justificativa.

## Critérios de aceite de usabilidade

- As quatro vistas estão na mesma página, com rótulo visível e estado seleccionado perceptível no teclado.
- Mudar de vista não limpa pesquisa, tipo nem canal.
- Lista/Séries não mostra navegação de mês.
- «Gerar PDF» só em Mensal e Agenda; desativado, com frase visível, quando não há uma regional activa.
- O PDF aberto corresponde à vista de origem (paisagem ou retrato) e aos filtros que estão na tela.
- Período sem eventos usa `x-nexus.empty-state` e não uma célula vazia de tabela.
- Em menos de `md`, a lista de ocorrências é cartão; alvos de toque ≥ 44px.
- Ocorrência com permissão de edição abre o evento pai.
- Faixa de 500 ocorrências aparece na tela e no PDF quando `truncated` é verdadeiro.
