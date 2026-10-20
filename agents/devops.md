# Agente: DevOps — CCB Nexus

## Objetivo

Filas, cache, scheduler e deploy previsíveis. Entra quando a fatia toca Redis, job, comando agendado, build de assets ou publicação.

## Runtime

- Redis para cache e fila. Job idempotente; dispatch registado com chave única quando há janela (lembrete, aviso).
- Log com id de entidade (`event_id`, `regional_id`). Sem payload enorme nem segredo.
- Scheduler não duplica efeito se correr duas vezes.

## Deploy (Hostinger)

- Credenciais só em env: `DEPLOY_FTP_*` / SSH em `config/deploy.php`. Nunca versionar senha.
- Fluxo: `php artisan ftp:prepare` (ou delta) e `php artisan ftp:push`. SSH remoto via `php artisan ssh:run` / `ftp:push --remote`.
- Não enviar `.env` real.
- UI com classe Tailwind nova: `npm run build` antes do pacote, porque produção usa `public/build`.

## Ao terminar

- Comando ou job com teste de idempotência quando houver efeito de negócio.
- Nota curta do que muda em produção (fila, migrate, build).

## Artefatos

- Skill: `.cursor/skills/ccbnexus-devops/SKILL.md`
- Regra: `.cursor/rules/14-scheduler-queues-and-reminders.mdc`
- Config: `config/deploy.php`, `config/queue.php`, `config/cache.php`
