<?php

namespace App\Livewire\Public;

use App\Models\AccessRequest;
use Livewire\Component;

class AccessRequestForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public function submit(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        AccessRequest::query()->create(array_merge($data, ['status' => 'pending']));
        session()->flash('status', __('Pedido enviado. Entraremos em contacto.'));
        $this->reset(['name', 'email', 'phone', 'message']);
    }

    public function render()
    {
        return view('livewire.public.access-request-form');
    }
}
