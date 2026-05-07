<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(UserManagementService $users)
    {
        $this->authorize('viewAny', User::class);

        $viewer = auth()->user();
        $query = $users->listingQuery($viewer);

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function (Builder $q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }

        return view('livewire.users.user-list', [
            'rows' => $query->paginate(15),
        ]);
    }
}
