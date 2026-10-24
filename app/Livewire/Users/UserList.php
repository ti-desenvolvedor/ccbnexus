<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use App\Services\Auth\PasswordResetLinkService;
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

    public ?string $statusMessage = null;

    public ?int $impersonateUserId = null;

    public string $impersonateName = '';

    public string $justification = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStrength(): void
    {
        $this->resetPage();
    }

    public function sendPasswordReset(int $userId, PasswordResetLinkService $links): void
    {
        $target = User::query()->findOrFail($userId);
        $this->authorize('sendPasswordReset', $target);

        $links->send(auth()->user(), $target);

        $this->statusMessage = 'Link de redefinição enviado para '.$target->email.'. A senha não é mostrada.';
    }

    public function askImpersonation(int $userId): void
    {
        $target = User::query()->findOrFail($userId);
        $this->authorize('impersonate', $target);

        $this->impersonateUserId = $target->id;
        $this->impersonateName = $target->name;
        $this->justification = '';
        $this->resetErrorBag('justification');
    }

    public function cancelImpersonation(): void
    {
        $this->impersonateUserId = null;
        $this->impersonateName = '';
        $this->justification = '';
    }

    public function startImpersonation(ImpersonationService $impersonation): void
    {
        if (! $this->impersonateUserId) {
            return;
        }

        $target = User::query()->findOrFail($this->impersonateUserId);
        $this->authorize('impersonate', $target);

        $impersonation->start(auth()->user(), $target, $this->justification);

        $this->redirect(route('dashboard'), navigate: false);
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
        } elseif ($this->strength === PasswordStrength::MEDIUM) {
            $query->where('password_strength', PasswordStrength::MEDIUM);
        } elseif ($this->strength === PasswordStrength::STRONG) {
            $query->where('password_strength', PasswordStrength::STRONG);
        }

        return view('livewire.users.user-list', [
            'rows' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
