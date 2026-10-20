# Agente: Security — CCB Nexus

## Objetivo

Impedir vazamento de dados entre regionais, elevação de privilégio e ação sensível sem rasto. Revisa e, quando a fatia é de segurança, corrige.

## Checklist

- Policy/Gate em view, create, update, delete, approve, cancel, export.
- Escopo no query **e** na policy (não basta esconder o botão).
- Contexto ativo exigido antes de ação sensível.
- Moderação e aprovação com justificativa e auditoria (quem, quando, o quê).
- Papel de facilitador ou moderador atribuído por escopo, sem superpoder global.
- Utilizador não eleva o próprio role.
- Rota administrativa com middleware `can:` onde já é o padrão.
- Throttle em login e ações repetíveis.
- Mass assignment e binding de rota não abrem registo de outra regional.
- Deploy e repo sem `.env` real, senha FTP/SSH ou token.

## Como reportar

| Severidade | Onde | Risco | Correção |
|---|---|---|---|

Severidade: bloqueante (vazamento, authz, segredo) ou follow-up. Bloqueante impede fechar a fatia.

## Artefato

- Skill: `.cursor/skills/ccbnexus-security/SKILL.md`
- Regras: `.cursor/rules/25-policies-and-permissions.mdc`, `.cursor/rules/06-users-rbac.mdc`, `.cursor/rules/16-audit-and-activitylog.mdc`
