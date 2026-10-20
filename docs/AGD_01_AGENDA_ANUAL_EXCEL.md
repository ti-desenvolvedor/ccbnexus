# AGD-01 — Agenda anual tipo Excel (backlog)

**Estado:** futuro — fora da Fase 1 Lista de Batismos.  
**Referência de seed de teste:** Agenda Excel 2026 Versão 09 via `RegionalEmbuArtesAgenda2026Seeder` + `database/seeders/data/agenda_embu_artes_2026.php`.

## Objetivo

Vista/exportação de agenda anual alinhada ao layout Excel (casas × meses), reutilizando séries recorrentes já modeladas (`monthly_nth` / `weekly` / `yearly`).

## Fora de escopo agora

- Calendário 2027 enxuto (SEED-01b) — só quando o PO entregar Agenda 2027 oficial.
- UI de grelha anual completa.

## Premissas quando for implementar

1. Ler séries com `meta.excel_key` / `meta.excel_rule`.
2. Não duplicar datas à mão se a regra couber no planner.
3. Exceções pontuais como eventos standalone (`meta.excel_exception`).
4. Respeitar canais: boletim (`include_on_baptism_list`) vs WhatsApp (`whatsapp_enabled`).
