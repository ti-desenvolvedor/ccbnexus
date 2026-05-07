<?php

namespace App\Livewire\Access;

use App\Models\AccessRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AccessRequestList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $review_note = '';

    public function approve(int $id): void
    {
        $req = AccessRequest::query()->findOrFail($id);
        $this->authorize('update', $req);
        $req->update([
            'status' => 'approved',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->review_note ?: null,
        ]);
        $this->review_note = '';
    }

    public function reject(int $id): void
    {
        $req = AccessRequest::query()->findOrFail($id);
        $this->authorize('update', $req);
        $req->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->review_note ?: null,
        ]);
        $this->review_note = '';
    }

    public function render()
    {
        $this->authorize('viewAny', AccessRequest::class);

        return view('livewire.access.access-request-list', [
            'requests' => AccessRequest::query()->orderByDesc('created_at')->paginate(15),
        ]);
    }
}
