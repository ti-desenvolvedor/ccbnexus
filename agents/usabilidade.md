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
- Painel: o bloco de listas de batismos resume **Para aprovação** e **Aprovadas**. O selo Publicado do evento não aparece no card de batismo da semana.
- **Command palette** (`Ctrl+K` / `/`) como acelerador; menu lateral continua canónico
- **Menu lateral**: o padrão é aberto, com os nomes. Em Perfil → Preferências da conta (ou no botão no rodapé do menu, em ecrã largo) a pessoa pode escolher **Minimizado**. Nesse modo, em `lg+`, fica uma faixa de ícones; o menu completo abre por cima do conteúdo ao passar o mouse, ao focar com o teclado, ou ao tocar numa secção. O conteúdo não muda de largura enquanto o menu está aberto. No telemóvel a gaveta continua com os nomes. Esc fecha o menu aberto por cima.
- Atalhos: `g d/e/r/u`, `n e/r`, `c` contexto, `?` cheatsheet — ignorar com foco em input
- Secção **Facilitador** no menu com badges de pendências. Quando essa secção está visível, Pedidos de acesso e Aprovações não se repetem em Organização nem em Agenda.
- Contexto no topbar: Administração e Casa ficam inativos até o nível anterior estar escolhido, com o motivo no `title` do controle.

## Mobile

- Inputs/botões `min-h-[44px]`
- Filtros `flex-col` → `lg:flex-row`
- Filas (acesso, aprovações, reservas): cards abaixo de `md`, não só tabela com scroll

## Moderação

- Aprovar/rejeitar: justificativa **obrigatória** + confirmação
- Confirmar a lista de batismos: cabeçalho com fundo clarinho (verde na confirmação, vermelho na negação) e título na cor de texto normal; no escuro o cartão fica escuro e o cabeçalho usa um tom fundo da mesma cor, sem texto claro sobre fundo claro. Corpo com ícone grande à esquerda e o texto à direita. Rodapé com Cancelar e a ação. Verificar e aprovar não pedem observação. Reprovar pede o que precisa ser ajustado. Nova revisão, atualizar a lista, aplicar período e restaurar períodos pedem confirmação porque refazem a revisão. Ocultar ou mostrar item ou secção não pede, porque desfaz no mesmo botão.
- Feedback toast/alert imediato

## Referências

- Skill: `.cursor/skills/ccbnexus-ux-designer/SKILL.md`
- Regra: `.cursor/rules/20-livewire-ux.mdc`
- Layout: `resources/views/layouts/app.blade.php`
- Componentes: `resources/views/components/ui/` e `resources/views/components/nexus/`
