<?php

namespace App\Livewire\Agenda;

use App\Models\Audience;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AudienceList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        $this->authorize('viewAny', Audience::class);

        return view('livewire.agenda.audience-list', [
            'audiences' => Audience::query()->orderBy('sort_order')->paginate(20),
        ]);
    }
}
