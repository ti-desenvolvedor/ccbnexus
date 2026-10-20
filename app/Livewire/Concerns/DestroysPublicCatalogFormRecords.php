<?php

namespace App\Livewire\Concerns;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

trait DestroysPublicCatalogFormRecords
{
    use AuthorizesRequests;

    public ?string $deleteError = null;

    protected function handleCatalogDelete(callable $action, string $redirectRoute, string $flashMessage): void
    {
        $this->deleteError = null;

        try {
            $action();
            session()->flash('status', $flashMessage);
            $this->redirect($redirectRoute, navigate: true);
        } catch (ValidationException $e) {
            $this->deleteError = $e->validator->errors()->first('delete') ?? __('Não foi possível excluir.');
        }
    }
}
