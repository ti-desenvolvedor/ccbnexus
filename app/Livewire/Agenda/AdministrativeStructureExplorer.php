<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Models\Administration;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class AdministrativeStructureExplorer extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;

    public string $scopeFilter = PublicDepartment::SCOPE_REGIONAL;

    public ?int $regionalId = null;

    public ?int $administrationId = null;

    public function mount(OrganizationalContextService $context, PublicAudienceCatalogService $catalog): void
    {
        $this->authorize('viewAny', PublicDepartment::class);

        $user = auth()->user();
        $this->regionalId = $context->activeRegionalId()
            ?? Regional::query()
                ->when(! $user?->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user?->accessibleRegionalIds() ?? []))
                ->orderBy('name')
                ->value('id');

        if ($this->regionalId) {
            $this->syncAdministrationFromContext($context);
        }
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(OrganizationalContextService $context): void
    {
        $rid = $context->activeRegionalId();
        if ($rid) {
            $this->regionalId = $rid;
        }
        $this->syncAdministrationFromContext($context);
    }

    protected function syncAdministrationFromContext(OrganizationalContextService $context): void
    {
        if (! $this->regionalId) {
            return;
        }

        $fromContext = $context->activeAdministrationId();
        if ($fromContext && Administration::query()->whereKey($fromContext)->where('regional_id', $this->regionalId)->exists()) {
            $this->administrationId = $fromContext;

            return;
        }

        $this->administrationId = Administration::query()
            ->where('regional_id', $this->regionalId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->value('id');
    }

    public function updatedRegionalId(): void
    {
        $this->administrationId = Administration::query()
            ->where('regional_id', $this->regionalId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->value('id');
    }

    public function updatedScopeFilter(): void
    {
        if ($this->scopeFilter === PublicDepartment::SCOPE_REGIONAL) {
            $this->administrationId = null;
        }
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->where('is_active', true)->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->where('is_active', true)->orderBy('name')->get();

        $administrations = collect();
        $structure = collect();

        if ($this->regionalId) {
            $administrations = Administration::query()
                ->where('regional_id', $this->regionalId)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();

            $structure = $catalog->administrativeStructureForRegional(
                $user,
                (int) $this->regionalId,
                $this->scopeFilter,
                $this->scopeFilter === PublicDepartment::SCOPE_ADMINISTRATION ? $this->administrationId : null,
            );
        }

        return view('livewire.agenda.administrative-structure-explorer', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'structure' => $structure,
        ]);
    }
}
