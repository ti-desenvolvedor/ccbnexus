# ADR — Lista de Batismos e Diversos + Agenda Excel 2026

**Status:** Aceite  
**Data:** 2026-10-15  
**Contexto:** Secretaria Regional Embu das Artes; processo manual Excel → Marcação → Word/PDF.

## Decisões

### 1. Canais por evento

| Campo | Significado |
|-------|-------------|
| `include_on_baptism_list` | Entra no builder da Lista de Batismos |
| `whatsapp_enabled` | Já existia — aviso WhatsApp |
| `whatsapp_groups` (M2M) | Destinos específicos |

Defaults por `event_types.meta.default_include_on_baptism_list` / `default_whatsapp_enabled`.

### 2. Recorrência vs Agenda Excel

Motor atual (`monthly_nth` / `weekly` / `yearly`) cobre a maior parte. Brás multi-slot, Juquitiba misto e mocidade com nth diferentes → **vários pais**. Seed **idêntico** à Agenda 2026 (`recurrence_until` = 2026-12-31) para testes. 2027 enxuto = backlog.

### 3. Relatórios

1. **Lista de Batismos e Diversos** — boletim PDF (layout 2026-10-15 R00), rascunho + revisões Rn + moderação `is_visible`; inclui secção coletas.
2. **Relatório de Marcações** — conferência M+2/M+3 (espelha `MARCACAO DE SERVICOS`).
3. Agenda anual Excel-like — backlog (AGD-01).

### 4. Schema novo

- `events.include_on_baptism_list`, `events.has_libras`
- `baptism_list_reports` / `baptism_list_report_items`
- `regional_notices`, `regional_schedule_changes`, `regional_special_collections`
- `event_type_audience_presets` + pivot position slugs
- `whatsapp_groups` + `event_whatsapp_group`

### 5. Segurança

Policy = permissão + escopo regional + estado. Builder ignora `include_on_baptism_list=false`.

## Review LBD-08 (checklist)

- [x] Seed só Embu das Artes (`slug` regional)
- [x] Idempotente via `meta.excel_key`
- [x] Filhos via `EventRecurrenceService`
- [x] Eventos só-WA (`include_on_baptism_list=false`) fora do PDF
- [x] PDF secções + coletas/alterações/avisos
- [x] Canais na Agenda (filtro + badges) e no EventForm
- [ ] Checklist amostral manual vs Excel Versão 09 (PO / secretaria)
