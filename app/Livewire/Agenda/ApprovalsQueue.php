<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\Approval;
use App\Models\Event;
use App\Services\EventService;
use App\Services\OrganizationalContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ApprovalsQueue extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;

    /** @var array<int, string> */
    public array $notes = [];

    public function approve(int $id, EventService $events): void
    {
        $this->decide($id, $events, true);
    }

    public function reject(int $id, EventService $events): void
    {
        $this->decide($id, $events, false);
    }

    protected function decide(int $id, EventService $events, bool $approve): void
    {
        $approval = Approval::query()->findOrFail($id);
        $this->authorize('update', $approval);

        $note = trim((string) ($this->notes[$id] ?? ''));
        if ($note === '') {
            $this->addError('notes.'.$id, $approve
                ? __('Escreva por que este evento pode ser publicado.')
                : __('Escreva por que este evento não pode ser publicado.'));

            return;
        }

        $this->resetErrorBag('notes.'.$id);

        $model = $approval->approvable;
        if ($model instanceof Event) {
            $this->authorize('approve', $model);
            if ($approve) {
                $events->approve($model, auth()->user(), $note);
            } else {
                $events->reject($model, auth()->user(), $note);
            }
        }

        unset($this->notes[$id]);
        session()->flash('status', $approve ? __('Evento aprovado.') : __('Evento rejeitado.'));
    }

    public function render(OrganizationalContextService $context)
    {
        $this->authorize('viewAny', Approval::class);

        $user = auth()->user();

        $items = Approval::query()
            ->where('status', 'pending')
            ->where('approvable_type', Event::class)
            ->whereHasMorph('approvable', [Event::class], function ($q) use ($user, $context) {
                if (! $user->isSuperAdmin()) {
                    $ids = $user->accessibleRegionalIds();
                    $q->whereIn('regional_id', $ids);
                }

                $rid = $context->activeRegionalId();
                if ($rid) {
                    $q->where('regional_id', $rid);
                }
            })
            ->with(['approvable.location', 'requestedBy'])
            ->orderBy('created_at')
            ->paginate($this->paginatePerPage());

        return view('livewire.agenda.approvals-queue', [
            'approvals' => $items,
        ]);
    }
}
