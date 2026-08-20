<?php

namespace App\Livewire\Cms;

use App\Models\Bank;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $totalWorkers = Worker::count();
        $totalBanks = Bank::count();
        $totalUsers = User::count();
        $newWorkersThisMonth = Worker::where('created_at', '>=', now()->startOfMonth())->count();

        $bankDistribution = Worker::selectRaw('banks.initial, banks.name, count(*) as total')
            ->join('banks', 'banks.id', '=', 'workers.bank_id')
            ->groupBy('banks.initial', 'banks.name')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $bankLabels = $bankDistribution->pluck('initial')->values()->all();
        $bankData = $bankDistribution->pluck('total')->values()->all();

        $bankTable = $bankDistribution->map(function ($row) use ($totalWorkers) {
            $row->percent = $totalWorkers > 0 ? round($row->total / $totalWorkers * 100, 1) : 0;

            return $row;
        });

        $monthly = Worker::query()
            ->orderBy('created_at')
            ->get(['created_at'])
            ->groupBy(fn ($w) => $w->created_at->format('Y-m'))
            ->map->count();

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $key = Carbon::now()->subMonths($i)->format('Y-m');
            $months[$key] = $monthly[$key] ?? 0;
        }

        $monthLabels = $months->keys()->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M'))->values()->all();
        $monthData = $months->values()->all();

        $recentWorkers = Worker::with('bank')->latest()->take(5)->get();

        return view('livewire.cms.dashboard', compact(
            'totalWorkers',
            'totalBanks',
            'totalUsers',
            'newWorkersThisMonth',
            'bankLabels',
            'bankData',
            'bankTable',
            'monthLabels',
            'monthData',
            'recentWorkers'
        ));
    }
}
