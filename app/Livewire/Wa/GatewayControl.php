<?php

namespace App\Livewire\Wa;

use App\Services\Wa\GatewayProcessManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class GatewayControl extends Component
{
    use AuthorizesRequests;

    public bool $enabled = false;

    /** @var array{available: bool, running: bool, pid: ?int, message: string} */
    public array $process = [];

    public function mount(): void
    {
        $this->refresh();
    }

    #[On('gateway-process-changed')]
    public function refresh(): void
    {
        $manager = app(GatewayProcessManager::class);

        $this->enabled = $manager->enabled();
        $this->process = $manager->status();
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
        $result = app(GatewayProcessManager::class)->{$action}();
        $this->refresh();

        $this->dispatch('gateway-process-changed');

        $this->dispatch(
            'notify',
            type: $result['ok'] ? 'success' : 'danger',
            message: $result['message'],
        );
    }

    public function render()
    {
        return view('livewire.wa.gateway-control');
    }
}
