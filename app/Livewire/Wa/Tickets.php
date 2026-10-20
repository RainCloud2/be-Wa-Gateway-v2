<?php

namespace App\Livewire\Wa;

use App\Livewire\Traits\WithPerPagePagination;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Tickets extends Component
{
    use AuthorizesRequests, WithPagination, WithPerPagePagination;

    public string $search = '';

    public string $status = '';

    public string $category = '';

    public string $priority = '';

    public string $assignee = '';

    public function render()
    {
        $tickets = Ticket::query()
            ->with(['employee', 'assignee'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender', Message::SENDER_EMPLOYEE)->whereNull('read_at');
            }])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('title', 'like', '%'.$this->search.'%')
                        ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
            ->when($this->assignee, fn ($q) => $q->where('assignee_id', $this->assignee))
            ->orderByRaw("CASE WHEN status = 'OPEN' THEN 0 WHEN status = 'IN_PROGRESS' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate($this->perPage)
            ->withPath(request()->url());

        $categoryCounts = Ticket::query()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('livewire.wa.tickets', [
            'tickets' => $tickets,
            'statuses' => Ticket::statuses(),
            'categories' => Ticket::CATEGORIES,
            'priorities' => Ticket::priorities(),
            'technicians' => User::orderBy('name')->get(['id', 'name']),
            'categoryCounts' => $categoryCounts,
        ]);
    }

    public function take(int $id): void
    {
        $this->authorize('ticket.update');

        $ticket = Ticket::findOrFail($id);

        if ($ticket->assignee_id && $ticket->assignee_id !== auth()->id()) {
            $this->dispatch('notify', type: 'warning', message: 'Tiket sudah ditangani teknisi lain.');

            return;
        }

        $ticket->update([
            'assignee_id' => auth()->id(),
            'status' => $ticket->status === Ticket::STATUS_OPEN ? Ticket::STATUS_IN_PROGRESS : $ticket->status,
        ]);

        $this->dispatch('notify', type: 'success', message: "Tiket {$ticket->code} berhasil diambil.");
    }
}
