<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class CatalogAxisExplorer extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;

    /** operational | ministry | music */
    public string $axis = 'operational';

    public string $scopeFilter = PublicDepartment::SCOPE_REGIONAL;

    public ?int $regionalId = null;

    public ?int $administrationId = null;

    public ?int $prayerHouseId = null;

    public function mount(OrganizationalContextService $context): void
    {
        $this->authorize('viewAny', PublicDepartment::class);

        $user = auth()->user();
        $this->regionalId = $context->activeRegionalId()
            ?? Regional::query()
                ->when(! $user?->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user?->accessibleRegionalIds() ?? []))
                ->orderBy('name')
                ->value('id');

        if ($this->regionalId) {
            $this->syncContextAnchors($context);
        }
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(OrganizationalContextService $context): void
    {
        $rid = $context->activeRegionalId();
        if ($rid) {
            $this->regionalId = $rid;
        }
        $this->syncContextAnchors($context);
    }

    protected function syncContextAnchors(OrganizationalContextService $context): void
    {
        if (! $this->regionalId) {
            return;
        }

        $adminFromContext = $context->activeAdministrationId();
        if ($adminFromContext && Administration::query()->whereKey($adminFromContext)->where('regional_id', $this->regionalId)->exists()) {
            $this->administrationId = $adminFromContext;
        } else {
            $this->administrationId = Administration::query()
                ->where('regional_id', $this->regionalId)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->value('id');
        }

        if ($this->axis === 'operational' || $this->axis === 'music') {
            $houseFromContext = $context->activePrayerHouseId();
            if ($houseFromContext && PrayerHouse::query()->whereKey($houseFromContext)->whereHas('administration', fn ($q) => $q->where('regional_id', $this->regionalId))->exists()) {
                $this->prayerHouseId = $houseFromContext;
            } elseif ($this->administrationId) {
                $this->prayerHouseId = PrayerHouse::query()
                    ->where('administration_id', $this->administrationId)
                    ->orderByDesc('is_active')
                    ->orderBy('name')
                    ->value('id');
            }
        }
    }

    public function updatedRegionalId(): void
    {
        $this->administrationId = Administration::query()
            ->where('regional_id', $this->regionalId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->value('id');

        $this->prayerHouseId = $this->administrationId
            ? PrayerHouse::query()->where('administration_id', $this->administrationId)->orderByDesc('is_active')->orderBy('name')->value('id')
            : null;
    }

    public function updatedScopeFilter(): void
    {
        if ($this->axis === 'ministry' && $this->scopeFilter === PublicDepartment::SCOPE_PRAYER_HOUSE) {
            $this->scopeFilter = PublicDepartment::SCOPE_REGIONAL;
        }

        if ($this->scopeFilter === PublicDepartment::SCOPE_REGIONAL) {
            $this->administrationId = null;
            $this->prayerHouseId = null;
        } elseif ($this->scopeFilter === PublicDepartment::SCOPE_ADMINISTRATION) {
            $this->prayerHouseId = null;
        }
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->where('is_active', true)->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->where('is_active', true)->orderBy('name')->get();

        $administrations = collect();
        $houses = collect();
        $structure = collect();

        if ($this->regionalId) {
            $administrations = Administration::query()
                ->where('regional_id', $this->regionalId)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();

            $houses = PrayerHouse::query()
                ->whereHas('administration', fn ($q) => $q->where('regional_id', $this->regionalId))
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();

            $structure = match ($this->axis) {
                'ministry' => $catalog->ministryStructureForRegional(
                    $user,
                    (int) $this->regionalId,
                    $this->scopeFilter,
                    $this->scopeFilter === PublicDepartment::SCOPE_ADMINISTRATION ? $this->administrationId : null,
                ),
                'music' => $catalog->musicStructureForRegional(
                    $user,
                    (int) $this->regionalId,
                    $this->scopeFilter,
                    $this->scopeFilter === PublicDepartment::SCOPE_ADMINISTRATION ? $this->administrationId : null,
                    $this->scopeFilter === PublicDepartment::SCOPE_PRAYER_HOUSE ? $this->prayerHouseId : null,
                ),
                default => $catalog->operationalStructureForRegional(
                    $user,
                    (int) $this->regionalId,
                    $this->scopeFilter,
                    $this->scopeFilter === PublicDepartment::SCOPE_ADMINISTRATION ? $this->administrationId : null,
                    $this->scopeFilter === PublicDepartment::SCOPE_PRAYER_HOUSE ? $this->prayerHouseId : null,
                ),
            };
        }

        return view('livewire.agenda.catalog-axis-explorer', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'houses' => $houses,
            'structure' => $structure,
        ]);
    }
}
