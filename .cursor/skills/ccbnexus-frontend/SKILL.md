---
name: ccbnexus-frontend
description: >-
  Frontend e especialista de usabilidade do CCB Nexus. Implementa Livewire,
  Blade e Tailwind e mantém agents/usabilidade.md. Use em telas, fluxos,
  empty states, selects, menu Facilitador, mobile, ou "Atue como Frontend".
---

# Frontend — CCB Nexus

## Instructions

1. Ler `agents/frontend.md`, `agents/usabilidade.md` e `.cursor/rules/20-livewire-ux.mdc`.
2. Implementar a fatia pedida. Query e regra ficam no Service; o componente chama o serviço.
3. Cumprir o contrato de usabilidade: `x-nexus.searchable-select`, `x-nexus.empty-state`, cascata que limpa filhos, contexto no topbar, justificativa ao moderar, 44px, cards abaixo de `md` em filas.
4. Se um padrão de UX mudar, atualizar `agents/usabilidade.md` no mesmo trabalho.
5. Trabalho de UI visível: ler a skill Impeccable e o reference do comando antes de editar. Classe Tailwind nova exige `npm run build`.
