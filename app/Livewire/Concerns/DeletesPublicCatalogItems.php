<?php

namespace App\Livewire\Concerns;

use App\Models\Audience;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

trait DeletesPublicCatalogItems
{
    use AuthorizesRequests;

    public ?string $deleteError = null;

    public function deletePublicPosition(int $id, PublicAudienceCatalogDeleteService $deleter): void
    {
        $this->deleteError = null;
        $position = PublicPosition::query()->findOrFail($id);
        $this->authorize('delete', $position);

        try {
            $deleter->deletePosition($position);
            session()->flash('status', __('Cargo excluído.'));
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete');
        }
    }

    public function deletePublicDepartment(int $id, PublicAudienceCatalogDeleteService $deleter, bool $withPositions = false): void
    {
        $this->deleteError = null;
        $department = PublicDepartment::query()->findOrFail($id);
        $this->authorize('delete', $department);

        try {
            $deleter->deleteDepartment($department, $withPositions);
            session()->flash('status', __('Departamento excluído.'));
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete');
        }
    }

    public function deletePublicSubgroup(int $id, PublicAudienceCatalogDeleteService $deleter): void
    {
        $this->deleteError = null;
        $subgroup = PublicSubgroup::query()->findOrFail($id);
        $this->authorize('delete', $subgroup);

        try {
            $deleter->deleteSubgroup($subgroup);
            session()->flash('status', __('Subgrupo excluído.'));
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete');
        }
    }

    public function deleteAudience(int $id): void
    {
        $this->deleteError = null;
        $audience = Audience::query()->findOrFail($id);
        $this->authorize('delete', $audience);

        try {
            if ($audience->events()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => __('Não é possível excluir: esta audiência está ligada a eventos.'),
                ]);
            }

            $audience->delete();
            session()->flash('status', __('Audiência excluída.'));
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete');
        }
    }

    public function deletePublicGroup(int $id, PublicAudienceCatalogDeleteService $deleter): void
    {
        $this->deleteError = null;
        $group = PublicGroup::query()->findOrFail($id);
        $this->authorize('delete', $group);

        try {
            $deleter->deleteGroup($group);
            session()->flash('status', __('Grupo excluído.'));
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete');
        }
    }
}
