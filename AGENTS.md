## CCB Nexus — Papéis e Fluxo

Este projeto usa um fluxo fixo de “papéis” para guiar a implementação com consistência e escalabilidade.

### Fluxo obrigatório (sempre)

1. **Orquestrador**: quebra tarefas grandes, define ordem, delega e cria checklist de revisão. **Não gera código.**
2. **Arquiteto**: define estrutura, boundaries, models/services e relacionamentos visando baixo acoplamento.
3. **UX/UI Designer**: define layout, telas e usabilidade; agrupa campos e melhora o fluxo.
4. **Backend**: implementa em Laravel (migrations/models/services/validações). **Sem regra de negócio em controllers.**
5. **Frontend**: implementa UI com Livewire/Tailwind e integra com backend.
6. **Reviewer**: revisa arquitetura, padrões, segurança, edge cases e consistência.
7. **DevOps** (quando necessário): redis/queues/cache/performance/observabilidade.

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
  - **Endereços** (cadastro próprio) para **Administração** e **Casa de oração**
  - **Salas de reunião** podem pertencer a:
    - **Casa de oração**, ou
    - **Administração**
  - **Reservas de salas** (conflitos de agenda, cancelamento, histórico; aprovação quando necessário)
- Agenda / Eventos / Modelos / **Públicos** (taxonomia hierárquica — ver secção dedicada abaixo) / Papéis de evento / Estrutura
- Avisos automáticos e regras editáveis
- Aprovações, Auditoria, Relatórios, Secretari

### Regras de domínio importantes (multi-regional + permissões)

- **Escopo de dados**: quase tudo deve ser consultável/gerenciável apenas dentro do escopo autorizado (**Regional → Administração → Casa de oração**), com políticas explícitas para evitar vazamento entre regionais.
- **Contexto ativo**: quando um usuário tiver acesso a mais de um escopo, a UI deve exigir/selecionar **contexto ativo** (regional/administração/casa) antes de ações sensíveis.
- **RBAC**: além de papéis (roles), o sistema deve suportar **permissões específicas por recurso/ação** (ex.: gerenciar endereços, gerenciar salas, criar/cancelar reserva, aprovar reserva), sempre validadas com **Policy/Gate** considerando **permissão + escopo + estado**.
- **Facilitadores e moderadores (governança transversal)**:
  - **Facilitador**: apoia fluxos (acesso, cadastros, reservas, eventos), reduz atrito e garante que requisitos/comprovações estejam completos antes de avançar etapas.
  - **Moderador**: pode intervir em conteúdo/configurações sensíveis (ex.: regras de avisos, publicações/comunicações, casos de conflito em reservas), sempre com **justificativa** e **auditoria**.
  - Esses papéis devem existir como **capacidades explícitas** (permissões) e podem ser atribuídos por **escopo** (regional/administração/casa), sem “superpoder” global acidental.
- **Reservas**: impedir sobreposição de horários na mesma sala; registrar **auditoria** (quem/quando/o quê) e suportar fluxo de **aprovação** quando aplicável.
- **Endereços**: cadastro completo e consistente para **Administração** e **Casa de oração** (normalização de campos; geolocalização opcional no futuro).
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

### Comando inicial recomendado

> “Crie o módulo de autenticação completo seguindo o padrão definido”

