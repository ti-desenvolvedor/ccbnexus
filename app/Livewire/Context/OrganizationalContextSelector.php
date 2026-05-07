<?php

namespace App\Livewire\Context;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use Livewire\Component;

class OrganizationalContextSelector extends Component
{
    public null|string|int $regionalId = null;

    public null|string|int $administrationId = null;

    public null|string|int $prayerHouseId = null;

    public function mount(OrganizationalContextService $context): void
    {
        $this->regionalId = $context->activeRegionalId();
        $this->administrationId = $context->activeAdministrationId();
        $this->prayerHouseId = $context->activePrayerHouseId();
    }

    public function apply(OrganizationalContextService $context): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $rid = $this->regionalId === '' || $this->regionalId === null ? null : (int) $this->regionalId;
        $aid = $this->administrationId === '' || $this->administrationId === null ? null : (int) $this->administrationId;
        $hid = $this->prayerHouseId === '' || $this->prayerHouseId === null ? null : (int) $this->prayerHouseId;

        if (! $context->userMayUseContext($user, $rid, $aid, $hid)) {
            $this->addError('context', __('Contexto inválido para o seu utilizador.'));

            return;
        }

        $context->setActive($rid, $aid, $hid);
        $this->dispatch('nexus-context-updated');
    }

    public function getRegionalsProperty()
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        if ($user->isSuperAdmin()) {
            return Regional::query()->where('is_active', true)->orderBy('name')->get();
        }

        $ids = $user->accessibleRegionalIds();
        if ($ids === []) {
            return collect();
        }

        return Regional::query()->whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get();
    }

    public function getAdministrationsProperty()
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $q = Administration::query()->orderBy('name');
        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                return collect();
            }
            $q->whereIn('regional_id', $ids);
        }

        return $q->limit(200)->get();
    }

    public function getPrayerHousesProperty()
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $q = PrayerHouse::query()->with('administration')->orderBy('name');
        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                return collect();
            }
            $q->whereHas('administration', fn ($a) => $a->whereIn('regional_id', $ids));
        }

        return $q->limit(300)->get();
    }

    public function render()
    {
        return view('livewire.context.organizational-context-selector');
    }
}
