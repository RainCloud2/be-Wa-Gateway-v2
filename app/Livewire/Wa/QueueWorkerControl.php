<?php

namespace App\Livewire\Wa;

use App\Services\Wa\QueueWorkerProcessManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class QueueWorkerControl extends Component
{
    use AuthorizesRequests;

    public bool $enabled = false;

    /** @var array<string, mixed> */
    public array $process = [];

    public int $pending = 0;

    public int $failed = 0;

    public ?string $heartbeatDisplay = null;

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $manager = app(QueueWorkerProcessManager::class);
        $this->enabled = $manager->enabled();
        $this->process = $manager->status();
        $this->pending = DB::table((string) config('queue.connections.database.table', 'jobs'))->count();
        $this->failed = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();

        $this->heartbeatDisplay = ! empty($this->process['heartbeat_at'])
            ? Carbon::parse($this->process['heartbeat_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i:s')
            : null;
    }

    public function start(): void
    {
        $this->authorize('wa.update');

        $this->runAction('start');
    }

    public function stop(): void
    {
        $this->authorize('wa.update');

        $this->runAction('stop');
    }

    public function restart(): void
    {
        $this->authorize('wa.update');

        $this->runAction('restart');
    }

    private function runAction(string $action): void
    {
        $result = app(QueueWorkerProcessManager::class)->{$action}();
        $this->refresh();

        $this->dispatch('queue-process-changed');

        $this->dispatch(
            'notify',
            type: $result['ok'] ? 'success' : 'danger',
            message: $result['message'],
        );
    }

    public function render()
    {
        return view('livewire.wa.queue-worker-control');
    }
}
