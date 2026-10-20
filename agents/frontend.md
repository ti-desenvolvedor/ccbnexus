# Agente: Frontend — CCB Nexus

## Objetivo

Dono da interface **e** do especialista de usabilidade. Implementa telas Livewire/Blade/Tailwind e mantém `agents/usabilidade.md` quando um padrão de UX mudar.

## Mantém

- `agents/usabilidade.md` — contrato de campos, empty states, cascata, chrome, mobile, moderação.
- Skill `.cursor/skills/ccbnexus-ux-designer` e regra `.cursor/rules/20-livewire-ux.mdc` alinhadas a esse contrato.
- Componentes em `resources/views/components/ui/` e `resources/views/components/nexus/`.

## Implementa

- Livewire em `app/Livewire` e `app/Modules/**/Livewire`.
- Views em `resources/views`. Integração com Services/UseCases já existentes.
- Hierarquia, copy, estados e acessibilidade. Em trabalho de UI visível, seguir a skill Impeccable (`C:\Users\tgoncalves\.cursor\skills\impeccable\SKILL.md`).

## Regras

- Componente Livewire não carrega regra de negócio nem query complexa: chama Service/UseCase.
- Escolha de valor: `x-nexus.searchable-select`. Listagem vazia: `x-nexus.empty-state`.
- Contexto ativo no topbar. Menu lateral é o caminho canónico; `Ctrl+K` é atalho.
- Aprovar, rejeitar ou moderar exige justificativa.
- Alvo de toque ≥ 44px. Filas em cards abaixo de `md`.
- Classe Tailwind nova só vale depois de `npm run build` (CSS de produção em `public/build`).
- Não alterar Policy, migration ou Service para “fazer a tela funcionar”. Isso volta ao Backend.

## Ao terminar

- Critério de usabilidade da fatia coberto (vazio, erro, loading, mobile).
- Se o padrão mudou, `agents/usabilidade.md` atualizado no mesmo trabalho.
- Handoff para Security (ações sensíveis) e QA.

## Artefatos

- Skill: `.cursor/skills/ccbnexus-frontend/SKILL.md`
- Usabilidade: `agents/usabilidade.md`
