<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RoleDirectory extends Component
{
    use AuthorizesRequests;

    public function render()
    {
        $this->authorize('viewAny', User::class);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('livewire.users.role-directory', compact('roles'));
    }
}
