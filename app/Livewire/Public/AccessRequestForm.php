<?php

namespace App\Livewire\Public;

use App\Models\AccessRequest;
use App\Models\Administration;
use App\Models\Regional;
use Livewire\Component;

class AccessRequestForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public ?int $regional_id = null;

    public ?int $administration_id = null;

    public function updatedRegionalId(): void
    {
        $this->administration_id = null;
    }

    public function submit(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
            'regional_id' => ['required', 'exists:regionals,id'],
            'administration_id' => ['nullable', 'exists:administrations,id'],
        ]);

        if ($data['administration_id'] ?? null) {
            $adm = Administration::query()->find($data['administration_id']);
            if ($adm && (int) $adm->regional_id !== (int) $data['regional_id']) {
                $this->addError('administration_id', __('A administração não pertence à regional selecionada.'));

                return;
            }
        }

        AccessRequest::query()->create(array_merge($data, ['status' => 'pending']));
        session()->flash('status', __('Pedido enviado. Entraremos em contacto.'));
        $this->reset(['name', 'email', 'phone', 'message', 'regional_id', 'administration_id']);
    }

    public function render()
    {
        $regionals = Regional::query()->where('is_active', true)->orderBy('name')->get();
        $administrations = $this->regional_id
            ? Administration::query()->where('regional_id', $this->regional_id)->orderBy('name')->get()
            : collect();

        return view('livewire.public.access-request-form', [
            'regionals' => $regionals,
            'administrations' => $administrations,
        ]);
    }
}
