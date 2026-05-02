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
- Usuários (perfis/permissões)
- Agenda / Eventos / Modelos / Públicos / Papéis / Estrutura
- Avisos automáticos e regras editáveis
- Aprovações, Auditoria, Relatórios, Secretari

### Comando inicial recomendado

> “Crie o módulo de autenticação completo seguindo o padrão definido”

