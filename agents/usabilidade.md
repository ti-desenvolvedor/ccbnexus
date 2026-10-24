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

### Barra de progresso (`x-nexus.form-progress`)
No topo de cada formulário com **dois ou mais** campos obrigatórios para guardar. O formulário ocupa a largura do painel. No telemóvel os campos ficam numa coluna. A partir de `md`, um par divide a linha; a partir de `lg`, três campos do mesmo grupo (nome, e-mail e telefone, ou regional, administração e casa) dividem a linha. O campo que não tem par — texto longo, local, notas — ocupa a linha inteira, sem célula vazia ao lado. A barra, os títulos de secção e os botões ficam na linha inteira. O padrão é o já conhecido: barra da esquerda para a direita, “3 de 5”. Marcar com `data-progress-field` só o que a validação exige. Telefone, notas, complemento, ordem com valor inicial e mensagem livre não entram. Enquanto falta campo, o texto é “Faltam N campos”. Em 100%, “Tudo preenchido”. Confirmação de um único campo (motivo, senha para eliminar a conta, e-mail de recuperação) fica sem barra. A confirmação de presença já mostra os passos 1 e 2.

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

- Abaixo de `md` só a barra de ícones (64px) fica fixa no topo. O contexto e o título da página rolam com o conteúdo. A partir de `md` o topo inteiro volta a ser fixo.
- Contexto no telemóvel: uma linha “Contexto · regional · administração” que abre os três seletores. Abre sozinho quando falta a regional ou há erro.
- Gaveta do menu: no máximo 85% da largura, botão Fechar no topo, Esc e toque fora fecham, a página por trás não rola. Navegar fecha a gaveta.
- O botão rápido de tema sai do topo abaixo de `sm`; o tema continua em Aparência.
- Agenda no telemóvel abre na vista Agenda (dia a dia) quando a URL não fixa outra vista. Mensal continua disponível.
- Agenda: **Ocorrências** e **Séries**, e o filtro **Tipo**, ficam sempre visíveis em Calendário, Lista, Mensal e Agenda. Séries lista as séries da regional em qualquer vista; Ocorrências volta à vista escolhida. Situação e Canal continuam em Filtros no telemóvel.
- Listas com vários filtros: busca sempre visível; os outros filtros ficam atrás de “Filtros (N)” abaixo de `sm`.
- Abas de formulário longo (evento): grelha de 3 colunas no telemóvel, nunca faixa com scroll lateral.
- Cabeçalhos fixos dentro do conteúdo usam `max-md:top-16` para não ficarem sob a barra.
- Em ecrã de toque, links e botões dentro de tabelas têm 44px de altura (`pointer: coarse`).
- Inputs/botões `min-h-[44px]`
- Filtros `flex-col` → `lg:flex-row`
- Filas (acesso, aprovações, reservas): cards abaixo de `md`, não só tabela com scroll

## Moderação

- Aprovar/rejeitar: justificativa **obrigatória** + confirmação
- Confirmar a lista de batismos: cabeçalho com fundo clarinho (verde na confirmação, vermelho na negação) e título na cor de texto normal; no escuro o cartão fica escuro e o cabeçalho usa um tom fundo da mesma cor, sem texto claro sobre fundo claro. Corpo com ícone grande à esquerda e o texto à direita. Rodapé com Cancelar e a ação. Verificar e aprovar não pedem observação. Reprovar pede o que precisa ser ajustado. Nova revisão, atualizar a lista, aplicar período e restaurar períodos pedem confirmação porque refazem a revisão. Ocultar ou mostrar item ou secção não pede, porque desfaz no mesmo botão.
- Login: o link **Esqueci minha senha** fica debaixo do campo senha, com alvo de 44px, e abre **Recuperar senha**. O e-mail é texto simples, assunto **Redefinir senha no CCB Nexus**, com o endereço para definir a senha. A senha não vai no texto. O link abre **Nova senha** mesmo com alguém já autenticado. A recuperação não informa se o e-mail está cadastrado. O botão de login e o de recuperação enviam um pedido de cada vez. Nos formulários que definem senha nova (registo, nova senha, perfil e ficha do utilizador) a barra por baixo do campo mostra **Fraca**, **Média** ou **Forte**. Fraca não grava. Média cumpre 8 caracteres com maiúscula, minúscula, número e símbolo. Forte tem 12 caracteres. Enquanto a consulta de vazamentos não termina, a barra mostra **Analisando senha…**. Senha fraca ou vazada não grava: o botão de guardar fica desligado até a senha ser média ou forte e não constar em vazamentos. Login, confirmar a senha atual e eliminar a conta não mostram a barra, porque essa senha já existe.
- Usuários: a lista alterna o fundo das linhas. **Editar**, **Enviar link de senha** e **Personificar** ficam na mesma linha, cada um com ícone e rótulo, alvo de 44px. **Enviar link de senha** pede confirmação e não mostra a senha. **Personificar** pede o motivo do suporte (mínimo 10 caracteres) e troca a sessão para essa pessoa. A linha dessa pessoa fica âmbar enquanto o motivo está aberto. A faixa âmbar no topo mostra **Você** (conta de origem), **Vendo como** (nome, e-mail e âmbito) e **Nesta sessão** (tempo corrido e hora de início). **Encerrar** volta à conta de origem. **Sair** termina a sessão inteira.
- Confirmações e avisos usam o modal do Nexus (`x-nexus.dialog`). `wire:confirm` abre esse modal, com Cancelar e Confirmar; exclusão, remoção, rejeição e cancelamento usam o botão vermelho. Um aviso simples tem só OK. Não usar `alert`, `confirm` ou `prompt` do navegador. O corpo (ou o cabeçalho, se não houver texto extra) traz sempre um ícone: lixeira para excluir, foto riscada para remover foto, envelope para e-mail, proibido para cancelar, visto para aprovar, x para rejeitar, recarregar para página expirada, triângulo para aviso e círculo de informação para o restante. Alertas de página (`x-nexus.alert`) e mensagens curtas (`x-nexus.inline-message`) usam o mesmo conjunto e não ficam só em texto.
- Feedback toast/alert imediato

## Referências

- Skill: `.cursor/skills/ccbnexus-ux-designer/SKILL.md`
- Regra: `.cursor/rules/20-livewire-ux.mdc`
- Layout: `resources/views/layouts/app.blade.php`
- Componentes: `resources/views/components/ui/` e `resources/views/components/nexus/`
