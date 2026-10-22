<?php

namespace App\Livewire\Shell;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarDensityToggle extends Component
{
    public function setDensity(string $density): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $allowed = config('nexus.ui.sidebar_modes', ['expanded', 'icons']);
        if (! in_array($density, $allowed, true)) {
            return;
        }

        $user->ui_sidebar = $density;
        $user->save();

        $this->dispatch('sidebar-density-updated', density: $density);
    }

    public function render()
    {
        return view('livewire.shell.sidebar-density-toggle');
    }
}
