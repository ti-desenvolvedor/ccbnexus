<?php

namespace App\Livewire\Agenda;

use App\Models\Approval;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ApprovalsQueue extends Component
{
    use AuthorizesRequests;

    public function approve(int $id, EventService $events): void
    {
        $approval = Approval::query()->findOrFail($id);
        $this->authorize('update', $approval);
        $model = $approval->approvable;
        if ($model instanceof Event) {
            $this->authorize('approve', $model);
            $events->approve($model, auth()->user());
        }
    }

    public function reject(int $id, EventService $events): void
    {
        $approval = Approval::query()->findOrFail($id);
        $this->authorize('update', $approval);
        $model = $approval->approvable;
        if ($model instanceof Event) {
            $this->authorize('approve', $model);
            $events->reject($model, auth()->user());
        }
    }

    public function render()
    {
        $this->authorize('viewAny', Approval::class);

        $items = Approval::query()
            ->where('status', 'pending')
            ->where('approvable_type', Event::class)
            ->with(['approvable', 'requestedBy'])
            ->orderBy('created_at')
            ->paginate(20);

        return view('livewire.agenda.approvals-queue', [
            'approvals' => $items,
        ]);
    }
}
