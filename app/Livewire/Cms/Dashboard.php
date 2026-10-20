<?php

namespace App\Livewire\Cms;

use App\Http\Controllers\Api\Wa\StatusController;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Ticket;
use App\Services\Wa\WhatsAppDriver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public ?array $waStatus = null;

    public function mount(): void
    {
        $cached = Cache::get(StatusController::CACHE_KEY);
        $this->waStatus = is_array($cached) ? $cached : null;
        $this->formatWaStatus();
    }

    #[On('gateway-process-changed')]
    public function refreshGatewayStatus(): void
    {
        $this->waStatus = app(WhatsAppDriver::class)->status();
        $this->waStatus['updated_at'] = now()->toIso8601String();
        $this->formatWaStatus();
    }

    public function render()
    {
        $totalTickets = Ticket::count();
        $activeTickets = Ticket::whereIn('status', [
            Ticket::STATUS_OPEN,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_WAITING,
        ])->count();
        $completedTickets = Ticket::whereIn('status', [
            Ticket::STATUS_RESOLVED,
            Ticket::STATUS_CLOSED,
        ])->count();
        $activeEmployees = Employee::real()->where('is_active', true)->count();

        $statusCounts = Ticket::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusDistribution = collect(Ticket::statuses())
            ->map(fn (string $status) => [
                'status' => $status,
                'total' => (int) ($statusCounts[$status] ?? 0),
                'percent' => $totalTickets > 0
                    ? round((int) ($statusCounts[$status] ?? 0) / $totalTickets * 100, 1)
                    : 0,
            ]);

        $monthly = Ticket::query()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['created_at'])
            ->groupBy(fn (Ticket $ticket) => $ticket->created_at->format('Y-m'))
            ->map->count();

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $key = Carbon::now()->subMonths($i)->format('Y-m');
            $months[$key] = $monthly[$key] ?? 0;
        }

        $monthLabels = $months->keys()
            ->map(fn (string $month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M'))
            ->values()
            ->all();
        $monthData = $months->values()->all();

        $recentTickets = Ticket::query()
            ->with('employee')
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender', Message::SENDER_EMPLOYEE)->whereNull('read_at');
            }])
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.cms.dashboard', compact(
            'totalTickets',
            'activeTickets',
            'completedTickets',
            'activeEmployees',
            'statusDistribution',
            'monthLabels',
            'monthData',
            'recentTickets',
        ));
    }

    private function formatWaStatus(): void
    {
        if (is_array($this->waStatus) && ! empty($this->waStatus['updated_at'])) {
            $this->waStatus['updated_at_display'] = Carbon::parse($this->waStatus['updated_at'])
                ->timezone(config('app.display_timezone'))
                ->format('d/m/Y H:i');
        }
    }
}
