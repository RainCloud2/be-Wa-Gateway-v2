<?php

namespace App\Livewire\Wa;

use App\Models\ChatSession;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Wa\OutgoingMessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TicketDetail extends Component
{
    use AuthorizesRequests;

    public Ticket $ticket;

    public string $reply = '';

    public function mount(Ticket $ticket): void
    {
        $this->authorize('ticket.read');
        $this->ticket = $ticket;
        $this->markRead();
    }

    public function markRead(): void
    {
        $this->ticketMessageQuery()
            ->where('sender', Message::SENDER_EMPLOYEE)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function sendReply(): void
    {
        $this->authorize('ticket.update');
        $this->validate(['reply' => 'required|string|max:4000']);

        app(OutgoingMessageService::class)->createMessage(
            $this->ticket->session,
            Message::SENDER_TECH,
            $this->reply,
            ['ticket_id' => $this->ticket->id],
        );

        $this->reply = '';
        $this->dispatch('notify', type: 'success', message: 'Balasan terkirim ke karyawan.');
    }

    public function assignToMe(): void
    {
        $this->authorize('ticket.update');

        $this->ticket->update([
            'assignee_id' => auth()->id(),
            'status' => $this->ticket->status === Ticket::STATUS_OPEN ? Ticket::STATUS_IN_PROGRESS : $this->ticket->status,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Tiket berhasil diambil.');
    }

    public function updateStatus(string $status): void
    {
        $this->authorize('ticket.update');

        if (! in_array($status, Ticket::statuses(), true)) {
            return;
        }

        $current = $this->ticket->status;

        if (! $this->canTransition($current, $status)) {
            $this->dispatch('notify', type: 'warning', message: "Perubahan status {$current} ke {$status} tidak diizinkan.");

            return;
        }

        $data = ['status' => $status];

        if ($status === Ticket::STATUS_RESOLVED) {
            $data['resolved_at'] = now();
            $data['closed_at'] = null;
        }

        if ($status === Ticket::STATUS_CLOSED) {
            $data['closed_at'] = now();
        }

        if ($status === Ticket::STATUS_IN_PROGRESS) {
            // Reopening: clear the closing timestamps.
            $data['resolved_at'] = null;
            $data['closed_at'] = null;
        }

        $this->ticket->update($data);

        if ($status === Ticket::STATUS_CLOSED) {
            $this->ticket->session->update([
                'mode' => ChatSession::MODE_AI,
                'ticket_id' => null,
                'attempt_count' => 0,
                'awaiting_ticket_confirmation' => false,
            ]);
        }

        $isReopen = $status === Ticket::STATUS_IN_PROGRESS
            && in_array($current, [Ticket::STATUS_WAITING, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true);

        if ($isReopen) {
            $this->reattachSession();
        }

        if ($status === Ticket::STATUS_RESOLVED && $this->ticket->session->mode === 'human') {
            app(OutgoingMessageService::class)->createMessage(
                $this->ticket->session,
                Message::SENDER_AI,
                'Apakah masalah Anda sudah teratasi? Balas *YA* jika sudah, atau *BELUM* jika masih bermasalah.',
                ['ticket_id' => $this->ticket->id],
            );
        }

        $this->dispatch('notify', type: 'success', message: "Status tiket diubah menjadi {$status}.");
    }

    /**
     * Only these status changes are allowed. In particular a CLOSED ticket can
     * never be moved back to RESOLVED, which previously let a closed ticket be
     * re-opened by an accidental click.
     */
    private function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $allowed = [
            Ticket::STATUS_OPEN => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_IN_PROGRESS => [Ticket::STATUS_WAITING, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_WAITING => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_RESOLVED => [Ticket::STATUS_IN_PROGRESS],
            Ticket::STATUS_CLOSED => [Ticket::STATUS_IN_PROGRESS],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }

    /**
     * Return the conversation to human handling when a ticket is reopened, so
     * the "YA/BELUM" confirmation flow works again.
     */
    private function reattachSession(): void
    {
        $this->ticket->session->update([
            'mode' => ChatSession::MODE_HUMAN,
            'ticket_id' => $this->ticket->id,
            'awaiting_ticket_confirmation' => false,
        ]);
    }

    private function ticketMessageQuery(): Builder
    {
        $previous = Ticket::query()
            ->where('session_id', $this->ticket->session_id)
            ->where('id', '<', $this->ticket->id)
            ->latest('id')
            ->first();

        $start = $previous?->closed_at
            ?? $previous?->resolved_at
            ?? $previous?->opened_at
            ?? $this->ticket->session?->started_at
            ?? $this->ticket->session?->created_at
            ?? $this->ticket->opened_at;

        return Message::query()
            ->where('session_id', $this->ticket->session_id)
            ->when($start, fn (Builder $query) => $query->where('created_at', '>=', $start))
            ->when($this->ticket->closed_at, fn (Builder $query) => $query->where('created_at', '<=', $this->ticket->closed_at));
    }

    public function render()
    {
        $this->markRead();

        $messages = $this->ticketMessageQuery()
            ->orderBy('id')
            ->get();

        return view('livewire.wa.ticket-detail', [
            'messages' => $messages,
            'technicians' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
