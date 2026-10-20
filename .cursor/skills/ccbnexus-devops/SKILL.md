---
name: ccbnexus-devops
description: >-
  DevOps do CCB Nexus. Cuida de Redis, filas, scheduler idempotente, build
  Vite e deploy FTP/SSH Hostinger sem segredos no repo. Use em queue, cache,
  cron, ftp:push, npm run build, ou "Atue como DevOps".
---

# DevOps — CCB Nexus

## Instructions

1. Ler `agents/devops.md` e `.cursor/rules/14-scheduler-queues-and-reminders.mdc`.
2. Job e comando agendado são idempotentes. Log leva id de entidade, sem segredo.
3. Deploy: `config/deploy.php`, `ftp:prepare` / `ftp:push`. Credenciais só em env. Não incluir `.env` real.
4. Se a UI ganhou classe Tailwind, `npm run build` entra no pacote (`public/build`).
5. Deixar nota do que produção precisa (migrate, fila, build).
