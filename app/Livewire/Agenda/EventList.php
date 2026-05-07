<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Services\OrganizationalContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class EventList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(OrganizationalContextService $context)
    {
        $this->authorize('viewAny', Event::class);

        $user = auth()->user();
        $query = Event::query()->with(['regional', 'eventType'])
            ->where('is_occurrence', false)
            ->whereNull('parent_event_id')
            ->orderByDesc('starts_at');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('regional_id', $ids)->orWhereNull('regional_id');
            });
        }

        $rid = $context->activeRegionalId();
        if ($rid) {
            $query->where('regional_id', $rid);
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where('title', 'like', $s);
        }

        return view('livewire.agenda.event-list', [
            'events' => $query->paginate(12),
        ]);
    }
}
