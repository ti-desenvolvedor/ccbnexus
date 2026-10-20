---
target: todo o layout e o sistema do CCB Nexus
total_score: 21
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 3
target_identity: "file:C:\\www\\ccbnexus\\resources\\views"
timestamp: 2026-10-16T19-53-43Z
slug: resources-views
closed: true
---
Method: dual-agent (A: 17e5b2d8-620d-436c-8eb2-ba143b61678c · B: 9506df50-cf41-4bb9-a335-95cdf5a53c24)

Target: CCB Nexus, resources/views. Operate mode. Admin shell reviewed from source. Public homepage injected in the browser at http://ccbnexus.test/. Logged-in pages were not injected (dashboard redirects to login).

## Design Health Score

21/40 — Acceptable.

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Bell always shows a rose dot, then “Sem itens por enquanto”. Event rows print raw status. |
| 2 | Match System / Real World | 2 | Dashboard vs Painel, Utilizadores, Guardar, status pending_approval, badge WA. |
| 3 | User Control and Freedom | 2 | Cancelar evento and Aprovar/Rejeitar have no confirm and no undo. Catalog deletes do. |
| 4 | Consistency and Standards | 2 | Shared button/badge components exist, then forms and lists use raw links. Secretaria has no active state. |
| 5 | Error Prevention | 2 | Publicado is a select on the same tab as traje. |
| 6 | Recognition Rather Than Recall | 2 | Empty events tell the secretary to remember Regional Embu das Artes. |
| 7 | Flexibility and Efficiency | 3 | Ctrl+K and g/n shortcuts exist. Secretaria routes and bulk publish do not. |
| 8 | Aesthetic and Minimalist Design | 2 | Dashboard repeats the sidebar as equal cards. |
| 9 | Error Recovery | 2 | Field errors are text-xs text-rose-600 with no aria-describedby. cancel_reason has no @error. |
| 10 | Help and Documentation | 2 | Persistent help is the SaaS sidebar tip. ? opens shortcuts only. |
| **Total** | | **21/40** | **Acceptable** |

## Design Specificity Verdict

LLM assessment: interchangeable SaaS admin. Church words sit on a Figtree, slate, primary-600 shell. layouts/app.blade.php says the menu “já nasce SaaS”. The mark is a blue tile “CCB” plus “NEXUS”. Eight recolorable palettes mean the product has no fixed color. What is specific (batismos, Libras, traje, quem atende, casas de oração, recorrência) lives inside forms, not in the composition.

Deterministic scan: impeccable detect on resources/views exited 2 with 5 findings (4 warning, 1 advisory). overused-font ×3 in reports/baptism-list/pdf.blade.php (Arial). side-tab ×1 in components/responsive-nav-link.blade.php. codex-grid-background ×1 in errors/503.blade.php. Browser injection on the public homepage reported 3 nested-cards warnings in pages/marketing/home.blade.php (lines 29–40).

False positives: Arial in the PDF is a print face, not the app UI font. side-tab is a left border on a nav link, not a card. The grid on the 503 page is a real advisory. Nested cards on the public home are real.

The detector did not see the developer copy, the always-on notification dot, or the unlabeled inputs. Those came from the design review.

Visual overlays: visible only on the public homepage during the scan. The overlay server was stopped. No reliable overlay on the logged-in admin.

## Audit Health Score

12/20 — Acceptable.

| # | Dimension | Score | Key Finding |
|---|-----------|-------|-------------|
| 1 | Accessibility | 2 | Search inputs and header icon buttons have no accessible name. prefers-reduced-motion exists only on the 503 page. |
| 2 | Performance | 3 | No layout thrash found. Motion is short transitions. Runtime of the logged-in app was not profiled. |
| 3 | Theming | 2 | Shell uses CSS variables. Most screens use Tailwind slate/primary classes. |
| 4 | Responsive Design | 2 | Sidebar is off-canvas below lg. Many header and editor controls are under 44px. |
| 5 | Implementation Integrity | 3 | Five detector hits, most false positives. One real nested-card cluster on the public home. |
| **Total** | | **12/20** | **Acceptable** |

## Overall Impression

The weekly office work is real and specific inside the event form. The shell hides it behind a second copy of the menu and treats publish, cancel, and approve like ordinary settings. The single biggest opportunity is to make the first screen this week’s cultos, the approval count, and the secretaria jobs.

## What's Working

- The event form speaks the office: Lista de Batismos e Diversos, Libras, Social / Esporte fino, quem atende, controlador de som, enfermagem, manobrista.
- Recorrência is built for a weekly culto: Único / Semanal / Mensal / Anual, a 12-month grid, and a live resumo.
- Ctrl+K, shortcut keys, and the regional / administração / casa de oração context match how the office actually works.

## Priority Issues

### [P1] The desk is a module directory
Why it matters: Monday work is batismos, avisos, and next Sunday’s cultos. The home screen leads with counts of regionais and a grid of the same links as the sidebar. Secretaria is absent from the dashboard and from the command palette.
Fix: Home shows this week’s cultos, the approval count, and the secretaria jobs. Org setup stays in the sidebar.
Suggested command: /impeccable distill

### [P1] Publish, cancel, and approve are unlabeled system actions
Why it matters: Estado is a select (Rascunho / Pendente aprovação / Publicado) beside traje. Cancelar evento has no wire:confirm and no @error on the reason. Approvals share one note and one click, with no date or place on the row. Catalog deletes already confirm.
Fix: One primary act, Publicar, that restates boletim and WhatsApp. Confirm cancel with the event title. Each approval gets its own note, date, and place. Status labels in Portuguese.
Suggested command: /impeccable harden

### [P1] The chrome tells the secretary she is in a template
Why it matters: Sidebar tip at layouts/app.blade.php:392, footer, always-on rose dot at line 519, Painel vs Dashboard, Utilizadores, Guardar, WA, Facilitador, Audiências (legado). The Embu empty state is wrong for every other regional.
Fix: Delete the developer copy. One Brazilian Portuguese vocabulary. Empty state names the current context and offers Novo evento.
Suggested command: /impeccable clarify

### [P2] One culto is six equal tabs, and three facts are copied
Why it matters: Quem atende, Libras, and the baptism checkbox appear on Dados and again on Responsáveis. Labels are not tied to inputs. Tabs have no aria-selected.
Fix: One column for título, tipo, início, local, and quem atende. Template editing, WhatsApp, and logistics behind disclosure.
Suggested command: /impeccable layout

### [P2] Header controls have no accessible names, and the list cannot be scanned by status
Why it matters: Theme button is title-only. The bell has no name. The user menu has no aria-expanded. Event search is placeholder-only. There is no status filter.
Fix: Name the three buttons. Associate the search label. Add a status filter and badge tones for Publicado, Pendente, and Rascunho.
Suggested command: /impeccable adapt

## Persona Red Flags

Alex (power user): command palette omits Lista de Batismos, Marcações, Avisos, Alterações de culto, and Coletas. The event table has no batch publish. Aparência (8 colors) sits in the header on every screen. The context selector is mounted twice on the dashboard.

Jordan (first-timer secretary): the nav says Dashboard and the title says Painel. Facilitador, Canal, and Eixo música are undefined. The sidebar tip explains collapsible SaaS chrome. The empty list names Regional Embu das Artes. Novo evento competes with eight identical atalho tiles.

Sam (keyboard / screen reader): event search has no label; theme and notification buttons have no accessible name; account image alt is empty; event-form labels are not associated; tab buttons have no role=tab; the events table has no caption.

## Minor Observations

- brand-logo uses h-15 w-15, which is not in the default Tailwind scale, and an empty span inside the tile.
- Compact context already applies on change, and the Aplicar button still sits in the bar.
- Metric labels skip __(). Card titles are divs, so there is one h1 and no section headings.
- Facilitador, Secretaria, and the three Eixo links never get aria-current. Pedidos de acesso, Aprovações, and WhatsApp each appear twice.
- Boletim and WA chips are the same gray. The filter calls the same idea Canal, Lista de Batismos, Só boletim, and Só WhatsApp.
- Public homepage nests three bordered panels inside one bordered panel (marketing/home.blade.php).

## Cognitive load

7 of 8 checklist items fail. Grouping passes. High cognitive load. Decision points over 4 options: full sidebar, expanded Agenda (about ten siblings), Secretaria (7), Organização (6), appearance (2 themes + 8 palettes), event form (6 tabs), dashboard shortcuts (up to 8), estrutura (7 checkboxes), recurrence month grid (12).

## Questions to Consider

- If Monday is batismos, avisos, and next Sunday’s cultos, why is the first screen a count of regionais?
- What does Publicado promise — boletim, WhatsApp, or both — and why is that a dropdown beside traje?
- Who is Facilitador to the person approving a culto, and why do Aprovações, WhatsApp, and Novo evento each live in two menus?
