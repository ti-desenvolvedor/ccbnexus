# Especialista de usabilidade (UX / UI operacional) — CCB Nexus

Mantido pelo agente **Frontend** (`agents/frontend.md`). Este ficheiro é o contrato; a implementação vive nas views e nos componentes Livewire.

## Missão

Garantir que formulários e fluxos do painel Nexus sejam rápidos, claros e utilizáveis em desktop e mobile — especialmente para **Facilitadores** (filas de acesso, aprovações, reservas, eventos) e operação multi-regional.

## Padrões de campos

### Select com busca (`x-nexus.searchable-select`)
Todo campo de escolha usa este componente (listas curtas e longas).

- Aberto, mostra as primeiras opções e filtra enquanto se digita
- Busca sem acento; ↑ ↓ Enter Esc
- Alvos de toque ≥ 44px
- Placeholders em português: `Buscar sala...`, `Selecione...`
- Estado vazio: **"Nenhum resultado encontrado"**

### Campos obrigatórios
- Indicador `*` no label
- Mensagens curtas e acionáveis em português
- Em formulários com abas (EventForm): mostrar contagem de erros **por aba**

### Estados vazios
- Listas: `x-nexus.empty-state` com ícone + texto + CTA quando `can:create`
- Selects sem opções: hint + link para o cadastro pai (ex.: “Crie uma sala em Operação → Salas”)

### Selects dependentes (cascata)
- Regional → Administração → Casa de oração (`wire:model.live` + limpar filhos)
- Desabilitar filho até o pai estar preenchido

## Chrome e produtividade

- **Contexto ativo** sempre visível no topbar (não só Dashboard/Profile)
- **Command palette** (`Ctrl+K` / `/`) como acelerador; menu lateral continua canónico
- Atalhos: `g d/e/r/u`, `n e/r`, `c` contexto, `?` cheatsheet — ignorar com foco em input
- Secção **Facilitador** no menu com badges de pendências

## Mobile

- Inputs/botões `min-h-[44px]`
- Filtros `flex-col` → `lg:flex-row`
- Filas (acesso, aprovações, reservas): cards abaixo de `md`, não só tabela com scroll

## Moderação

- Aprovar/rejeitar: justificativa **obrigatória** + confirmação
- Confirmar a lista de batismos: justificativa obrigatória; o botão fica bloqueado enquanto houver item visível ainda não publicado
- Feedback toast/alert imediato

## Referências

- Skill: `.cursor/skills/ccbnexus-ux-designer/SKILL.md`
- Regra: `.cursor/rules/20-livewire-ux.mdc`
- Layout: `resources/views/layouts/app.blade.php`
- Componentes: `resources/views/components/ui/` e `resources/views/components/nexus/`
