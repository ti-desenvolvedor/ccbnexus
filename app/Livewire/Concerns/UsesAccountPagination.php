<?php

namespace App\Livewire\Concerns;

trait UsesAccountPagination
{
    protected function paginatePerPage(): int
    {
        return auth()->user()?->resolvedListPerPage()
            ?? (int) config('nexus.pagination.default', 20);
    }
}
