<?php

namespace App\Livewire\Secretaria;

use App\Models\BaptismListReport;
use App\Models\BaptismListReportItem;
use App\Models\Regional;
use App\Services\BaptismListReportBuilder;
use App\Services\BaptismListReportConfirmation;
use App\Services\OrganizationalContextService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class BaptismListReportPage extends Component
{
    use AuthorizesRequests;

    public string $issue_date = '';

    public ?int $reportId = null;

    public string $screen = 'listagem';

    public string $confirmation_justification = '';

    /** @var array<string, array{from: string, to: string}> */
    public array $sectionFilters = [];

    public function mount(OrganizationalContextService $context): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin() || auth()->user()?->can('visualizar_relatorios'),
            403
        );
        $this->issue_date = now()->toDateString();
        $this->resetSectionFiltersToDefaults();
        $this->syncReportToActiveRegional($context);
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(mixed $payload = null): void
    {
        $this->syncReportToActiveRegional(app(OrganizationalContextService::class));
    }

    protected function syncReportToActiveRegional(OrganizationalContextService $context): void
    {
        $rid = $context->activeRegionalId();
        if (! $rid) {
            $this->reportId = null;

            return;
        }

        $current = $this->currentReport();
        if ($current && (int) $current->regional_id === (int) $rid) {
            return;
        }

        $latest = BaptismListReport::query()
            ->where('regional_id', $rid)
            ->orderByDesc('issue_date')
            ->orderByDesc('revision')
            ->first();

        if ($latest) {
            $this->reportId = $latest->id;
            $this->issue_date = $latest->issue_date->toDateString();
            $this->syncSectionFiltersFromReport($latest);

            return;
        }

        $this->resetSectionFiltersToDefaults();
        $user = auth()->user();
        if (! $user || ! $user->canMaintainSecretaria()) {
            $this->reportId = null;

            return;
        }

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $rid,
            Carbon::parse($this->issue_date ?: now()->toDateString()),
            $user,
            false,
            $this->sectionFilters,
        );
        $this->reportId = $report->id;
        $this->syncSectionFiltersFromReport($report);
    }

    public function updatedIssueDate(mixed $value = null, mixed $key = null): void
    {
        $this->resetSectionFiltersToDefaults();
    }

    public function setScreen(string $screen): void
    {
        if (in_array($screen, ['listagem', 'visualizacao', 'versoes'], true)) {
            $this->screen = $screen;
        }
    }

    public function selectRevision(int $reportId, OrganizationalContextService $context): void
    {
        $report = BaptismListReport::query()->findOrFail($reportId);
        $this->authorizeReport($report);
        $rid = $context->activeRegionalId();
        abort_unless($rid && (int) $report->regional_id === (int) $rid, 403);
        $this->reportId = $report->id;
        $this->issue_date = $report->issue_date->toDateString();
        $this->syncSectionFiltersFromReport($report);
        $this->screen = 'listagem';
    }

    public function generate(BaptismListReportBuilder $builder, OrganizationalContextService $context): void
    {
        $this->runGenerate($builder, $context, false);
    }

    public function generateNewRevision(BaptismListReportBuilder $builder, OrganizationalContextService $context): void
    {
        $this->runGenerate($builder, $context, true);
    }

    public function applySectionFilter(string $sectionKey, BaptismListReportBuilder $builder, OrganizationalContextService $context): void
    {
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $this->validate([
            "sectionFilters.{$sectionKey}.from" => ['required', 'date'],
            "sectionFilters.{$sectionKey}.to" => ['required', 'date', 'after_or_equal:sectionFilters.'.$sectionKey.'.from'],
        ]);
        $this->runGenerate($builder, $context, false);
    }

    public function resetSectionFilters(BaptismListReportBuilder $builder, OrganizationalContextService $context): void
    {
        $this->resetSectionFiltersToDefaults();
        $this->runGenerate($builder, $context, false);
    }

    protected function runGenerate(BaptismListReportBuilder $builder, OrganizationalContextService $context, bool $newRevision): void
    {
        $user = auth()->user();
        abort_unless($user?->canMaintainSecretaria(), 403);
        $rid = $context->activeRegionalId();
        if (! $rid) {
            session()->flash('status', __('Selecione uma regional ativa no filtro de contexto.'));

            return;
        }
        $this->validate(['issue_date' => ['required', 'date']]);
        $report = $builder->generateOrSync(
            $rid,
            Carbon::parse($this->issue_date),
            auth()->user(),
            $newRevision,
            $this->sectionFilters,
        );
        $this->reportId = $report->id;
        $this->syncSectionFiltersFromReport($report);
        session()->flash('status', __('Lista gerada (R'.$report->revision.').'));
    }

    public function confirmList(BaptismListReportConfirmation $confirmation, OrganizationalContextService $context): void
    {
        $report = $this->currentReport();
        abort_unless($report, 404);
        $this->authorize('confirm', $report);
        $confirmed = $confirmation->confirm(
            $report,
            auth()->user(),
            $this->confirmation_justification,
            $context->activeRegionalId(),
        );
        $this->confirmation_justification = '';
        $this->reportId = $confirmed->id;
        session()->flash('status', __('Lista confirmada (R'.sprintf('%02d', $confirmed->revision).').'));
    }

    public function toggleItem(int $itemId): void
    {
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $item = BaptismListReportItem::query()->findOrFail($itemId);
        $this->authorizeReport($item->report);
        $item->update(['is_visible' => ! $item->is_visible]);
    }

    public function hideSection(string $sectionKey): void
    {
        $report = $this->currentReport();
        if (! $report) {
            return;
        }
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $this->authorizeReport($report);
        $report->items()->where('section_key', $sectionKey)->update(['is_visible' => false]);
    }

    public function showSection(string $sectionKey): void
    {
        $report = $this->currentReport();
        if (! $report) {
            return;
        }
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $this->authorizeReport($report);
        $report->items()->where('section_key', $sectionKey)->update(['is_visible' => true]);
    }

    public function hideBras(): void
    {
        $report = $this->currentReport();
        if (! $report) {
            return;
        }
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $this->authorizeReport($report);
        foreach ($report->items as $item) {
            $label = (string) ($item->payload['location_label'] ?? '');
            if (str_contains(mb_strtolower($label), 'brás') || str_contains(mb_strtolower($label), 'bras')) {
                $item->update(['is_visible' => false]);
            }
        }
    }

    public function render(OrganizationalContextService $context)
    {
        $report = $this->currentReport();
        $builder = app(BaptismListReportBuilder::class);
        $periodHint = '';
        if ($this->issue_date) {
            $issue = Carbon::parse($this->issue_date);
            $longEnd = $builder->periodEndForIssue($issue);
            $shortEnd = $builder->defaultPeriodEndForSection('ensaios', $issue);
            $periodHint = __('Padrão: Batismos/Mocidade/Jovens até :long · demais secções até :short (ajustável por filtro).', [
                'long' => $longEnd->format('d/m/Y'),
                'short' => $shortEnd->format('d/m/Y'),
            ]);
        }

        $grouped = $report ? $this->groupedAllSections($report) : [];
        $regionalId = $context->activeRegionalId();
        $revisions = $regionalId
            ? BaptismListReport::query()
                ->where('regional_id', $regionalId)
                ->orderByDesc('issue_date')
                ->orderByDesc('revision')
                ->get(['id', 'issue_date', 'revision', 'status', 'generated_at'])
            : collect();
        $pendingItems = $report
            ? $report->items->filter(fn (BaptismListReportItem $item) => ($item->payload['status'] ?? null) === 'pending_approval')->values()
            : collect();
        $visiblePending = $pendingItems->filter(fn (BaptismListReportItem $item) => $item->is_visible)->values();
        $canMaintainSecretaria = (bool) auth()->user()?->canMaintainSecretaria();
        $canConfirmList = $report
            && $canMaintainSecretaria
            && $report->status === BaptismListReport::STATUS_DRAFT
            && $regionalId
            && (int) $report->regional_id === (int) $regionalId;

        return view('livewire.secretaria.baptism-list-report-page', [
            'report' => $report,
            'revisions' => $revisions,
            'pendingItems' => $pendingItems,
            'canConfirmList' => $canConfirmList,
            'confirmationBlocked' => $visiblePending->isNotEmpty(),
            'displayBlocks' => $report ? $this->buildDisplayBlocks($grouped) : [],
            'periodHint' => $periodHint,
            'regional' => $context->activeRegionalId()
                ? Regional::query()->find($context->activeRegionalId())
                : null,
            'hiddenCount' => $report ? $report->items()->where('is_visible', false)->count() : 0,
            'sectionTitles' => BaptismListReportBuilder::SECTION_TITLES,
            'longSections' => BaptismListReportBuilder::LONG_PERIOD_SECTIONS,
            'introListSections' => BaptismListReportBuilder::INTRO_LIST_MEETING_SECTIONS,
            'regionalMeetingsKey' => BaptismListReportBuilder::REGIONAL_MEETINGS_PERIOD_KEY,
        ]);
    }

    /**
     * Agrupa organistas/obra/RA/RRM/RGA num único bloco com um filtro.
     *
     * @param  array<string, Collection<int, BaptismListReportItem>>  $grouped
     * @return list<array{type: string, key: string, items: Collection<int, BaptismListReportItem>}>
     */
    protected function buildDisplayBlocks(array $grouped): array
    {
        $blocks = [];
        $narrativeAdded = false;

        foreach (array_keys(BaptismListReportBuilder::SECTION_ORDER) as $key) {
            if (in_array($key, BaptismListReportBuilder::regionalBlockSectionKeys(), true)) {
                if ($narrativeAdded) {
                    continue;
                }
                $narrativeAdded = true;
                $items = collect();
                foreach (BaptismListReportBuilder::regionalBlockSectionKeys() as $nk) {
                    if (isset($grouped[$nk])) {
                        $items = $items->concat($grouped[$nk]);
                    }
                }
                $blocks[] = [
                    'type' => 'regional_meetings',
                    'key' => BaptismListReportBuilder::REGIONAL_MEETINGS_PERIOD_KEY,
                    'items' => $items->values(),
                ];

                continue;
            }

            if (isset($grouped[$key]) || isset($this->sectionFilters[$key])) {
                $blocks[] = [
                    'type' => 'section',
                    'key' => $key,
                    'items' => $grouped[$key] ?? collect(),
                ];
            }
        }

        return $blocks;
    }

    public function hideRegionalMeetings(): void
    {
        $report = $this->currentReport();
        if (! $report) {
            return;
        }
        $this->authorizeReport($report);
        $report->items()
            ->whereIn('section_key', BaptismListReportBuilder::regionalBlockSectionKeys())
            ->update(['is_visible' => false]);
    }

    public function showRegionalMeetings(): void
    {
        $report = $this->currentReport();
        if (! $report) {
            return;
        }
        $this->authorizeReport($report);
        $report->items()
            ->whereIn('section_key', BaptismListReportBuilder::regionalBlockSectionKeys())
            ->update(['is_visible' => true]);
    }

    protected function resetSectionFiltersToDefaults(): void
    {
        if (! $this->issue_date) {
            $this->sectionFilters = [];

            return;
        }
        $this->sectionFilters = app(BaptismListReportBuilder::class)
            ->defaultSectionPeriods(Carbon::parse($this->issue_date));
    }

    protected function syncSectionFiltersFromReport(BaptismListReport $report): void
    {
        $defaults = app(BaptismListReportBuilder::class)
            ->defaultSectionPeriods($report->issue_date->copy());
        $stored = is_array($report->meta['section_periods'] ?? null)
            ? $report->meta['section_periods']
            : [];
        $this->sectionFilters = array_replace($defaults, $stored);
    }

    protected function currentReport(): ?BaptismListReport
    {
        return $this->reportId
            ? BaptismListReport::query()->with(['items', 'regional'])->find($this->reportId)
            : null;
    }

    protected function authorizeReport(BaptismListReport $report): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        abort_unless(in_array($report->regional_id, $user->accessibleRegionalIds(), true), 403);
    }

    /**
     * @return array<string, Collection<int, BaptismListReportItem>>
     */
    protected function groupedAllSections(BaptismListReport $report): array
    {
        $grouped = $report->items->groupBy('section_key');
        $ordered = [];
        foreach (array_keys(BaptismListReportBuilder::SECTION_ORDER) as $key) {
            if ($grouped->has($key)) {
                $ordered[$key] = $grouped->get($key);
            }
        }
        foreach ($grouped as $key => $items) {
            if (! isset($ordered[$key])) {
                $ordered[$key] = $items;
            }
        }

        return $ordered;
    }
}
