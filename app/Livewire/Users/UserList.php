<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\User;
use App\Services\UserManagementService;
use App\Support\PasswordStrength;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use UsesAccountPagination;
    use WithPagination;

    public string $search = '';

    public string $strength = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStrength(): void
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

        if ($this->strength === 'review') {
            $query->where(function (Builder $q) {
                $q->where('password_strength', PasswordStrength::WEAK)
                    ->orWhere('password_strength', PasswordStrength::UNKNOWN)
                    ->orWhereNull('password_strength');
            });
        } elseif ($this->strength === PasswordStrength::STRONG) {
            $query->where('password_strength', PasswordStrength::STRONG);
        }

        return view('livewire.users.user-list', [
            'rows' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
