---
target: listagem da agenda
total_score: 20
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 3
target_identity: "file:c:\\www\\ccbnexus\\resources\\views\\livewire\\agenda"
timestamp: 2026-10-16T20-57-41Z
slug: resources-views-livewire-agenda
---
Method: dual-agent (A: b8b597e0-0cea-4b57-93a4-ac15c3a95474 · B: 62afb44b-f525-466d-bdcc-b296b9d7d7e1)

## Design Health Score

| # | Heurística | Nota | Problema |
|---|---|---|---|
| 1 | Visibilidade do estado | 2 | A aba e o período respondem, mas não há contagem. «Gerar PDF» some em Calendário e Lista sem aviso. |
| 2 | Correspondência com o mundo real | 3 | O vocabulário de culto está certo. «Calendário» é a semana, a Agenda escreve «WA», e o filtro «Lista de batismos» vira «Boletim». |
| 3 | Controlo e liberdade | 2 | Anterior, Hoje e Seguinte funcionam. Não há «limpar filtros» nem salto de mês. |
| 4 | Consistência | 2 | O mesmo culto é link azul no mês, bloco verde na semana, «WA» na agenda e selo «WhatsApp» na tabela. |
| 5 | Prevenção de erro | 2 | Cancelado e Rejeitado devolvem o período vazio, e o vazio oferece «Novo evento». |
| 6 | Reconhecimento em vez de memória | 2 | É preciso lembrar que Lista é o mês, Séries é o arquivo, e «+6» esconde cultos. |
| 7 | Flexibilidade | 2 | Há busca e URL. Não há atalho de período nem ação em lote. |
| 8 | Estética e minimalismo | 2 | A grelha de outubro espreme cultos em 12px e corta o domingo. |
| 9 | Recuperação de erro | 2 | O vazio manda ajustar pesquisa, tipo ou canal. Não explica Cancelado nem Rejeitado. |
| 10 | Ajuda | 1 | Não há legenda de Séries/Ocorrências nem de canal. |
| **Total** | | **20/40** | **Aceitável** |

## Design Specificity Verdict

O conteúdo é da Congregação Cristã no Brasil: Regional Embu das Artes, RCO, RMA, batismo, obra da piedade. A composição ainda é um calendário administrativo que serviria a qualquer sala de reuniões. Trocar os títulos por «reunião» e «sala» deixaria o mesmo produto.

O detector de markup (`resources/views/livewire/agenda`) saiu limpo: 0 achados. No browser, o overlay marcou 5 contrastes baixos na vista Mensal. Quatro são os dias do mês vizinho (28, 29, 30 e 1, `#94a3b8` sobre `#f8fafc`, 2.5:1): chrome esbatido de propósito, falso positivo para o objetivo de «dia fora do mês». O quinto é o rótulo «Todos» do filtro de tipo (`#94a3b8` sobre branco, 2.6:1): controlo visível, contraste real. Lista, Calendário e Agenda não acrescentaram achados.

## Overall Impression

Outubro de 2026 abre com cultos reconhecíveis e a regional certa. O vale é a grelha: o sábado 10 mostra três reuniões e «+6», e o domingo sai da área visível. A secretária termina sem saber se deixou um culto passar.

## What's Working

- O contexto «Regional Embu das Artes» está na página, e outubro traz casas e tipos reais.
- A vista Agenda ordena por dia em português, com intervalo, tipo e local. Anterior, Hoje e Seguinte mudam o período.
- A Lista de ocorrências lê data, casa, canal e situação numa linha. A busca «Batismo» e o filtro WhatsApp devolvem só o que prometem.

## Priority Issues

### [P1] A grelha Mensal esconde culto
No sábado 10/10 a célula mostra três reuniões da Piedade e «+6». O domingo fica cortado à direita.
Por que importa: Mensal é a vista inicial de quem não pode perder um culto.
Correção: mostrar o domingo inteiro e abrir só o dia excedente, com o «+N» a dizer o dia.
Comando sugerido: /impeccable layout

### [P1] Cancelado e Rejeitado parecem um mês sem culto
Esses filtros devolvem zero linhas e o texto «Nenhum evento neste período», com botão «Novo evento». A leitura já exclui esses estados.
Por que importa: a secretária conclui que nada foi cancelado, ou cria um duplicado.
Correção: dizer que esta leitura não inclui cancelados nem rejeitados, e trocar o botão por «Limpar situação».
Comando sugerido: /impeccable clarify

### [P1] O Calendário enterra o culto da noite
A semana de 12–18 out começa às 07:00. A primeira tela chega perto das 17:00. RMA e Culto Para Jovens, às 19:30, ficam no fim, todos no mesmo verde.
Por que importa: o horário que ela confere é o último a aparecer.
Correção: abrir a grade perto das 18:00 e marcar o dia de hoje também na lista.
Comando sugerido: /impeccable layout

### [P2] Séries parece a lista do mês
A tabela não tem período nem regra de repetição. A página 1 mostra 20 de 95, da Santa Ceia de dezembro para trás.
Por que importa: ela procura o culto de sábado e encontra outro mês.
Correção: uma linha de recorrência e o rótulo «séries da regional, não o mês».
Comando sugerido: /impeccable distill

### [P2] O canal muda de nome conforme a vista
O filtro diz «Lista de batismos» e «WhatsApp». A Agenda imprime «Boletim» e «WA». A tabela usa «Boletim» e «WhatsApp». «Quem atende» aparece como «—».
Por que importa: ela não sabe se o culto sai no boletim ou só no grupo.
Correção: um único nome de canal e o lugar vazio escrito por extenso.
Comando sugerido: /impeccable clarify

## Persona Red Flags

**Secretária da regional:** no Mensal, o sábado 10 e o domingo não cabem no primeiro olhar. «+6» troca a vista inteira para a Agenda. «Inclui rascunhos» está sempre visível, e a grelha não distingue rascunho de publicado. Cancelado e Rejeitado devolvem o mesmo vazio de uma busca sem resultado. Séries tira outubro da tela.

**Coordenador que já conhece a agenda:** não há atalho de período. Ctrl+K busca páginas do sistema, não cultos. A paginação de Séries («1 até 20 de 95») fica abaixo da dobra.

**Quem abre a tela pela primeira vez:** quatro vistas, 19 tipos, 6 situações e 5 canais ao mesmo tempo. Não há legenda. O seletor de cima mostra Administração e Casa desabilitadas, enquanto a linha da página só nomeia a regional.

## Cognitive load

Sete falhas: foco único, blocos digestíveis, hierarquia, uma decisão de cada vez, escolhas mínimas, memória de trabalho e revelação progressiva. O agrupamento dos filtros passa. Carga alta.

Decisões com mais de 4 opções: Tipo (19), Situação (6), Canal (5). As quatro vistas ficam no limite, somadas à barra Regional / Administração / Casa.

## Minor Observations

- O título da Agenda aparece como «Sábado, 3 De Outubro» por causa da capitalização automática.
- O vazio da semana também diz «mude o mês».
- «Gerar PDF» existe em Mensal e Agenda e segue a busca e a situação. Não existe em Calendário nem em Lista.
- Não há contagem do resultado. «Batismo» passou de 34 para 7 sem dizer isso.
- O rótulo «Todos» do tipo está a 2.6:1. Os números dos dias vizinhos, a 2.5:1, são o esbatido do mês ao lado.

Não há PRODUCT.md. `/impeccable init` grava o contexto do produto para as próximas passagens.

## Questions to Consider

1. O primeiro olhar deve ser o sábado e o domingo à noite, ou o mês inteiro com cultos atrás de «+6»?
2. Cancelado e Rejeitado devem sair do filtro, ou continuar com a frase de que esta leitura não os inclui?
3. Séries deve continuar o arquivo de 95 pais, ou mostrar só as séries que têm culto no mês aberto?
