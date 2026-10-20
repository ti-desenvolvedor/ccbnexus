<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\OfficeOverview;
use Livewire\Component;

class Metrics extends Component
{
    public bool $ready = false;

    /** @var array<string, mixed> */
    public array $panel = [];

    protected $listeners = [
        'nexus-context-updated' => 'refresh',
    ];

    public function refresh(OfficeOverview $overview): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $this->panel = $overview->snapshot($user);
        $this->ready = true;
    }

    public function render()
    {
        return view('livewire.dashboard.metrics');
    }
}
