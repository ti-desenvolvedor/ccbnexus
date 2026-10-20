<?php

namespace App\Livewire\Access;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\AccessRequest;
use App\Services\AccessRequestService;
use App\Services\OrganizationalContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

class AccessRequestList extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $review_note = '';

    public function approve(int $id, AccessRequestService $access): void
    {
        $req = AccessRequest::query()->findOrFail($id);
        $this->authorize('update', $req);

        $note = trim($this->review_note);
        if ($note === '') {
            $this->addError('review_note', __('Justificativa obrigatória para aprovar.'));

            return;
        }

        try {
            $access->approve($req, auth()->user(), $note);
        } catch (InvalidArgumentException $e) {
            $this->addError('review_note', $e->getMessage());

            return;
        }

        $this->review_note = '';
        session()->flash('status', __('Pedido aprovado e utilizador provisionado.'));
    }

    public function reject(int $id, AccessRequestService $access): void
    {
        $req = AccessRequest::query()->findOrFail($id);
        $this->authorize('update', $req);

        try {
            $access->reject($req, auth()->user(), trim($this->review_note) ?: null);
        } catch (InvalidArgumentException $e) {
            $this->addError('review_note', $e->getMessage());

            return;
        }

        $this->review_note = '';
        session()->flash('status', __('Pedido rejeitado.'));
    }

    public function render(OrganizationalContextService $context)
    {
        $this->authorize('viewAny', AccessRequest::class);

        $user = auth()->user();
        $query = AccessRequest::query()->with(['regional', 'administration'])->orderByDesc('created_at');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $active = $context->activeRegionalId();
            $query->where(function ($q) use ($ids, $active) {
                $q->whereIn('regional_id', $ids);
                if ($active) {
                    $q->orWhere('regional_id', $active);
                }
                $q->orWhereNull('regional_id');
            });
        }

        return view('livewire.access.access-request-list', [
            'requests' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
