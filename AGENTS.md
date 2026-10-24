## CCB Nexus — Papéis e Fluxo

Este projeto usa um fluxo fixo de “papéis” para guiar a implementação com consistência e escalabilidade.

### Fluxo obrigatório (sempre)

1. **Gerente de projetos**: quebra a entrega, define aceite, delega e só fecha a fatia com o checklist verde. **Não gera código.** (O nome antigo Orquestrador é este papel.)
2. **Backend**: schema, services, policies e validações. Boundaries e relacionamentos ficam aqui. **Sem regra de negócio em controllers.**
3. **Frontend**: implementa UI Livewire/Tailwind e **mantém** o especialista de usabilidade (`agents/usabilidade.md`).
4. **SQL performance** (quando há lista, filtro, agenda ou relatório): índices, N+1, plano de query.
5. **Security**: escopo multi-regional, authz, auditoria, segredos.
6. **QA**: testes de aceite e regressão.
7. **DevOps** (quando há fila, cache, scheduler ou deploy): Redis, build, FTP/SSH.

### Stack e padrões

- Laravel 10+, Livewire, TailwindCSS, MySQL, Redis
- SOLID, Clean Architecture, Service Layer, Repository (quando necessário)

### Módulos alvo (expansão)

- Acesso (login, solicitação e aprovação)
- Usuários (perfis/permissões **granulares e escopadas**)
- Organização operacional (multi-regional):
  - **Regional** (cada regional opera de forma independente)
  - **Administração** (subdivisão dentro da regional)
  - **Casa de oração** (pertence a uma administração)
- Infraestrutura física e logística:
  - **Locais** (`locations`): endereço reutilizável com **dono explícito** — `administration_id` (catálogo da administração) ou `regional_id` (sede regional). Ao cadastrar casa de oração, sala ou evento, os selects de local listam **somente** locais da administração/regional em contexto (sem vazamento entre Itapecerica, Embu, etc.).
  - **Endereços** morph (`addresses`) — legado/complementar; preferir `locations` nos formulários atuais
  - **Salas de reunião** podem pertencer a:
    - **Casa de oração**, ou
    - **Administração**
  - **Reservas de salas** (conflitos de agenda, cancelamento, histórico; aprovação quando necessário)
- Agenda / Eventos / Modelos / **Públicos** (taxonomia hierárquica — ver secção dedicada abaixo) / Papéis de evento / Estrutura
- Avisos automáticos e regras editáveis
- Aprovações, Auditoria, Relatórios
- **Secretaria — Lista de Batismos e Diversos** (boletim PDF, Rn, canais boletim/WA), Relatório de Marcações (M+2/M+3), avisos/alterações/coletas, grupos WhatsApp — ver `docs/ADR_LISTA_BATISMOS_E_AGENDA.md`; agenda anual Excel = `docs/AGD_01_AGENDA_ANUAL_EXCEL.md`

### Regras de domínio importantes (multi-regional + permissões)

- **Escopo de dados**: quase tudo deve ser consultável/gerenciável apenas dentro do escopo autorizado (**Regional → Administração → Casa de oração**), com políticas explícitas para evitar vazamento entre regionais.
- **Contexto ativo**: quando um usuário tiver acesso a mais de um escopo, a UI deve exigir/selecionar **contexto ativo** (regional/administração/casa) antes de ações sensíveis.
- **RBAC**: além de papéis (roles), o sistema deve suportar **permissões específicas por recurso/ação** (ex.: gerenciar endereços, gerenciar salas, criar/cancelar reserva, aprovar reserva), sempre validadas com **Policy/Gate** considerando **permissão + escopo + estado**.
- **Facilitadores e moderadores (governança transversal)**:
  - **Facilitador**: apoia fluxos (acesso, cadastros, reservas, eventos), reduz atrito e garante que requisitos/comprovações estejam completos antes de avançar etapas.
  - **Moderador**: pode intervir em conteúdo/configurações sensíveis (ex.: regras de avisos, publicações/comunicações, casos de conflito em reservas), sempre com **justificativa** e **auditoria**.
  - Esses papéis devem existir como **capacidades explícitas** (permissões) e podem ser atribuídos por **escopo** (regional/administração/casa), sem “superpoder” global acidental.
- **Reservas**: impedir sobreposição de horários na mesma sala; registrar **auditoria** (quem/quando/o quê) e suportar fluxo de **aprovação** quando aplicável.
- **Locais (endereços)**: cada registo em `locations` pertence a uma **administração** (obrigatório no cadastro normal) ou à **sede regional** (`regional_id`, sem administração). Filtros em listagens e formulários respeitam o escopo; super-admin também filtra pela administração/regional selecionada no formulário.
- **Slugs**: gerados automaticamente via `SlugService` a partir do nome (sem campo na UI); mantidos na edição para estabilidade de referências.
- **Salas**: sempre vinculadas a um “dono” explícito (**Administração** ou **Casa de oração**) para que permissões, reservas e relatórios respeitem o **escopo correto**.

### Públicos, grupos, departamentos e cargos (domínio ampliado)

O cadastro plano de “público” (`Audience` com nome/slug apenas) é **insuficiente** para secretaria, segmentação de comunicações e relatórios. Abaixo está a **modelagem alvo** sintetizada (analista + alinhamento organizacional). **Cadastro de pessoas** e vínculo pessoa ↔ estrutura **não** entram na primeira fatia de implementação, mas o desenho deve **antecipar** múltiplas filiações por pessoa.

#### Separação conceitual (obrigatória)

- **RBAC (Spatie — `roles` / `permissions`)**: quem pode **operar o sistema** (criar evento, aprovar reserva, moderar). Não misturar com a taxonomia abaixo.
- **Público / estrutura colaborativa (domínio CCB)**: grupos, subgrupos, **departamentos** e **cargos** usados para **classificar eventos**, **público-alvo**, **avisos**, relatórios e, no futuro, **roteamento** (ex.: aprovação por cargo responsável).

#### Entidades e hierarquia lógica

1. **Grupo de público** (raiz temática): ex. *Colaboradores administrativos*, *Colaboradores locais*, *Ministério*, *Música*, *Música — ensaio regional*. Atributos: nome, slug, ordem, ativo, opcionalmente `regional_id` quando o catálogo for regional.
2. **Subgrupo** (opcional): refinamento dentro do grupo; ex. *Colaboradores estatutários* sob *Colaboradores administrativos*. Um cargo pode ser apresentado na UI agrupado por subgrupo mesmo que no banco a chave estrangeira principal seja departamento + nome.
3. **Departamento** (entidade própria): unidade funcional (Presidência, Secretaria, Tesouraria, Saúde, etc.). Deve carregar **âmbito explícito**:
   - **Regional**: válido para toda a regional (ex.: tesouraria regional).
   - **Administração** ou **Casa de oração (local)**: departamentos que existem **abaixo** da regional, no nível administrativo ou na casa.
   - Regra de produto: **Regional → departamentos regionais**; **Administração** herda contexto regional e pode ter **departamentos locais**; **Casa de oração** tem departamentos **estritamente locais** quando aplicável. O arquiteto deve fixar FKs (`regional_id`, `administration_id`, `prayer_house_id`) com invariantes (ex.: não misturar escopos inválidos).
4. **Cargo / função** (folha selecionável): ex. *Presidente*, *Conselheiro fiscal*, *Tesoureiro*, *Ancião*, *Encarregado regional de música*. Pertence a **um departamento** (no sentido “lotação” organizacional). Campos típicos: nome, slug, `department_id`, ordem, ativo, `meta` para sinônimos ou notas.
5. **Colaboradores da administração e a regional**: os colaboradores vinculados à **administração** (e, por extensão, à estrutura que **compõe / sustenta a regional** no plano operacional) atuam em **departamentos** com âmbito regional ou local. Entre esses cargos destacam-se os **coordenadores de departamento** — responsáveis pela articulação do departamento naquele âmbito (ex.: coordenador da Secretaria regional, coordenador da Tesouraria na administração). No produto isto materializa-se como **cargo** no departamento correspondente, marcado quando aplicável como **coordenador de departamento** (flag explícita), para filtros, relatórios e futuras regras de notificação/aprovação.
6. **Classificações transversais**: exemplos como *Ativo imobilizado*, *Voluntário*, *Saúde* podem ser modelados como **subgrupos**, **tags** em `meta`, ou **departamentos** com tipo “transversal”, conforme decisão do arquiteto — o importante é não duplicar semântica entre três lugares.

#### Exemplos de catálogo (referência de negócio, não norma rígida)

- *Colaboradores administrativos* → *Estatutários* → cargos vinculados a departamentos (Presidência, Secretaria, Tesouraria): Presidente, Vice-presidentes, Secretário, Vice-secretários, Tesoureiro, Vice-tesoureiro, Conselho fiscal (titular/suplente), auxiliares, **coordenadores de departamento** (âmbito regional ou na administração que sustenta a regional), etc.; mais eixos como *Ativo imobilizado*, *Voluntário*, *Saúde* quando fizer sentido como subgrupo ou departamento.
- *Colaboradores locais* → cargos como *Tesouraria da escrita local* e reutilização de eixos (*Ativo imobilizado*, *Voluntário*, *Saúde*) com âmbito **local**.
- *Ministério* → Ancião, Diácono, Cooperador do ofício ministerial, Cooperador de jovens e menores (cada um com departamento/ministério adequado no catálogo).
- *Música* (e variantes como ensaio regional) → Anciãos (contexto musical), encarregados regional/local, irmãs examinadoras, etc., sempre com **âmbito** coerente (regional vs local).

#### Regras de cardinalidade (futuro — pessoas)

- Uma **pessoa** poderá estar em **vários** grupos, subgrupos e **cargos** simultaneamente (com vigência opcional). O modelo relacional deve usar **tabelas de junção** (N:N), nunca um único `cargo_id` na pessoa.
- **Escopo**: atribuições futuras de pessoa a cargo devem respeitar **Regional / Administração / Casa** como hoje em `users` e políticas, para evitar “cargo de outra regional” visível ou editável sem permissão.

#### Processos de produto melhorados

- **Eventos (aba Público)**: seleção por **cargo** (preferencial), com atalhos por **subgrupo** ou **grupo** (expandir seleção para todos os cargos filhos), com validação de escopo (só cargos do catálogo visível na regional/contexto ativo).
- **Avisos e relatórios**: filtros e segmentação pela mesma taxonomia.
- **Secretaria**: manutenção do **catálogo** versionado por regional (e overrides locais se o produto permitir), com auditoria em alterações sensíveis.

#### Entrega em fatias (orquestrador)

1. Schema + seeds de referência (departamentos por âmbito, grupos exemplo).  
2. CRUD de catálogo (Policy + escopo).  
3. Migração do conceito atual `audiences` plano para **compatibilidade** (ex.: audiência legada = grupo folha ou mapeamento 1:1 temporário) — decisão explícita no PR.  
4. Atualização da aba **Público** no evento para pivôs com grupo/subgrupo/cargo.  
5. Fase posterior: **pessoas** e matrículas N:N.

### Agentes e skills do projeto

| Papel | Documento / skill |
|---|---|
| Gerente de projetos | [`agents/gerente-projetos.md`](agents/gerente-projetos.md) · skill `.cursor/skills/ccbnexus-gerente` · regra `.cursor/rules/01-orchestrator.mdc` |
| Frontend (mantém usabilidade) | [`agents/frontend.md`](agents/frontend.md) · [`agents/usabilidade.md`](agents/usabilidade.md) · skills `ccbnexus-frontend` e `ccbnexus-ux-designer` |
| Backend | [`agents/backend.md`](agents/backend.md) · skill `.cursor/skills/ccbnexus-backend` |
| QA | [`agents/qa.md`](agents/qa.md) · skill `.cursor/skills/ccbnexus-qa` |
| Security | [`agents/security.md`](agents/security.md) · skill `.cursor/skills/ccbnexus-security` |
| SQL performance | [`agents/sql-performance.md`](agents/sql-performance.md) · skill `.cursor/skills/ccbnexus-sql-performance` |
| DevOps | [`agents/devops.md`](agents/devops.md) · skill `.cursor/skills/ccbnexus-devops` |
| Escopo mestre | [`docs/CCB_NEXUS_SCOPE.md`](docs/CCB_NEXUS_SCOPE.md) |

**Backlog vivo (prioridade):**

| Estado | Item |
|---|---|
| Feito | Time de agentes (gerente, front, back, QA, security, SQL, devops); register off; aprovações server-side; race/lock reservas; roles sem elevação; deploy sem `.env` real |
| Feito | Menu Facilitador, contexto no chrome, Ctrl+K, empty states, calendário salas, setup/teardown, pedido com regional |
| Feito | Policies com escopo (Parking/catálogo/WhatsApp/Approvals); middleware `can:`; throttle; status sem env em prod |
| Feito | Docs `DEPRECATE_AUDIENCES.md` + `RBAC_SCOPED_DESIGN.md` |
| Feito | Revisão 2026-10: escrita da secretaria separada de `visualizar_relatorios`; registo sem regional invisível fora do super-admin; pedido de acesso não cruza regional |
| Feito | Lista de batismos: verificação por pessoa (botão verificado) e aprovação da publicação só com pelo menos uma verificação |
| Feito | Recuperar senha visível no login; admin envia link de redefinição; impersonate de suporte com auditoria (`personificar_usuario` fora dos papéis por omissão) |
| Próximo | RBAC escopado (decisão de produto em `docs/RBAC_SCOPED_DESIGN.md`); retirar CRUD `audiences`; pessoas N:N |

### Impeccable (UI)

Usar em trabalho de **frontend**: desenhar, criticar, polir ou ajustar telas Livewire (hierarquia, layout, copy, estados, acessibilidade). Não usar em tarefas só de backend.

- **Entrada:** `C:\Users\tgoncalves\.cursor\skills\impeccable\SKILL.md`. Ler a skill e o reference do comando pedido (`shape`, `critique`, `polish`, etc.) e seguir esse playbook; não o copiar para aqui.
- **Passos:** correr o contexto uma vez na sessão (`scripts\impeccable.cmd context --target <ficheiro ou rota>`, cwd no projeto); carregar o reference do comando; antes de editar UI, ler `reference/craft-floor.md`.
- **CSS deste projeto:** o CSS de produção é o build Vite em `public/build`. Classes Tailwind novas **não** aplicam até `npm run build`. Preferir utilitários que já existem no CSS compilado.

### Dual-agent (critique)

Correr em `/impeccable critique` de uma superfície concreta (ficheiro ou URL), antes de um redesign ou depois de uma mudança de UI visível. Não substitui **Security** nem **QA**. Procedimento: `reference/critique.md`.

Dois subagentes **isolados e em paralelo** (nenhum vê o output do outro); o pai só sintetiza no fim:

1. **Assessment A (design review):** especificidade, hierarquia, heurísticas, carga cognitiva, jornada.
2. **Assessment B (detector + browser):** `impeccable detect` e evidência visual no browser.

O relatório abre com `Method: dual-agent`. Sem ferramenta de subagente, o fluxo é sequencial e o relatório tem de começar com o banner `DEGRADED`. A pergunta ao utilizador é a última linha.

### Comando inicial recomendado

> “Atue como Gerente de projetos CCB Nexus e decomponha a próxima fatia do backlog”

> “Crie o módulo de autenticação completo seguindo o padrão definido”

