<?php

namespace App\Livewire\Secretaria;

use App\Models\MinistryServant;
use App\Services\OrganizationalContextService;
use App\Services\PublicAudienceCatalogService;
use Livewire\Attributes\On;
use Livewire\Component;

class MinistryServantsPage extends Component
{
    public ?int $editingId = null;

    public string $servant_name = '';

    public string $servant_position_id = '';

    public string $servant_phone = '';

    public string $comum_prayer_house_id = '';

    public string $ordained_on = '';

    /** @var list<string> */
    public array $attendance_prayer_house_ids = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin() || auth()->user()?->can('visualizar_relatorios'),
            403
        );
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(): void
    {
        $this->resetForm();
    }

    public function edit(int $servantId, OrganizationalContextService $context): void
    {
        $servant = MinistryServant::query()
            ->with('attendanceHouses')
            ->where('regional_id', $this->regionalForWrite($context))
            ->findOrFail($servantId);

        $this->editingId = $servant->id;
        $this->servant_name = $servant->full_name;
        $this->servant_position_id = (string) ($servant->public_position_id ?? '');
        $this->servant_phone = (string) ($servant->phone ?? '');
        $this->comum_prayer_house_id = (string) ($servant->comum_prayer_house_id ?? '');
        $this->ordained_on = $servant->ordained_on?->toDateString() ?? '';
        $this->attendance_prayer_house_ids = $servant->attendanceHouses
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->resetErrorBag();
    }

    public function save(OrganizationalContextService $context): void
    {
        $regionalId = $this->regionalForWrite($context);
        if ($this->editingId) {
            abort_unless(
                MinistryServant::query()->whereKey($this->editingId)->where('regional_id', $regionalId)->exists(),
                404
            );
        }

        $comumId = $this->comum_prayer_house_id === '' ? null : (int) $this->comum_prayer_house_id;
        MinistryServant::saveProfile(
            $regionalId,
            $this->servant_name,
            (int) $this->servant_position_id,
            $this->servant_phone,
            $comumId,
            $this->attendance_prayer_house_ids,
            $this->editingId,
            $this->ordained_on,
        );

        $updated = $this->editingId !== null;
        $this->resetForm();
        session()->flash('servant_status', $updated ? 'Cadastro atualizado.' : 'Cadastro guardado.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(OrganizationalContextService $context, PublicAudienceCatalogService $catalog)
    {
        $regionalId = $context->activeRegionalId();
        $servants = $regionalId
            ? MinistryServant::query()
                ->with(['comum', 'attendanceHouses'])
                ->where('regional_id', $regionalId)
                ->orderBy('full_name')
                ->get()
            : collect();

        return view('livewire.secretaria.ministry-servants-page', [
            'servants' => $servants,
            'prayerHouses' => $regionalId ? MinistryServant::prayerHousesForRegional($regionalId) : collect(),
            'ministryOffices' => $regionalId ? $catalog->ministryOffices($regionalId) : collect(),
            'canRegisterServant' => (bool) $regionalId,
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->reset(
            'servant_name',
            'servant_position_id',
            'servant_phone',
            'comum_prayer_house_id',
            'ordained_on',
            'attendance_prayer_house_ids',
        );
        $this->resetErrorBag();
    }

    protected function regionalForWrite(OrganizationalContextService $context): int
    {
        $regionalId = $context->activeRegionalId();
        $user = auth()->user();
        abort_unless($regionalId && $user, 403);
        abort_unless($user->isSuperAdmin() || in_array($regionalId, $user->accessibleRegionalIds(), true), 403);

        return $regionalId;
    }
}
