<?php

namespace App\Livewire\Wa;

use App\Models\Employee;
use App\Models\Message;
use App\Services\Wa\IncomingMessageProcessor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin-only chat simulator. Messages are pushed through exactly the same
 * pipeline as real WhatsApp messages, but use reserved dummy numbers and never
 * reach the WhatsApp gateway.
 */
#[Layout('layouts.admin')]
class Simulator extends Component
{
    use AuthorizesRequests;

    public string $employeeId = '';

    public string $message = '';

    /** @var array<int, array<string, mixed>> */
    public array $thread = [];

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorize('wa.update');

        $persona = Employee::simulated()->orderBy('id')->first()
            ?? Employee::createSimulation();

        $this->employeeId = (string) $persona->id;
        $this->loadThread();
    }

    public function selectEmployee(string $id): void
    {
        $persona = Employee::simulated()->find($id);

        if (! $persona) {
            return;
        }

        $this->employeeId = (string) $persona->id;
        $this->message = '';
        $this->loadThread();
    }

    public function createPersona(): void
    {
        $this->authorize('wa.update');

        $persona = Employee::createSimulation();
        $this->employeeId = (string) $persona->id;
        $this->message = '';
        $this->loadThread();

        $this->dispatch('notify', type: 'success', message: 'Persona simulasi baru dibuat.');
    }

    public function send(): void
    {
        $this->authorize('wa.update');
        $this->validate(['message' => 'required|string|max:4000']);

        $persona = $this->resolvePersona();

        if (! $persona) {
            $this->addError('message', 'Pilih persona simulasi terlebih dahulu.');

            return;
        }

        app(IncomingMessageProcessor::class)->process([
            'wa_message_id' => 'SIM-'.Str::uuid(),
            'from' => $persona->phone,
            'push_name' => 'Simulator',
            'type' => 'text',
            'text' => $this->message,
            'timestamp' => time(),
            'simulated' => true,
        ], synchronous: true);

        $this->message = '';
        $this->loadThread();
    }

    public function resetSession(): void
    {
        $this->authorize('wa.update');

        $persona = $this->resolvePersona();

        if (! $persona) {
            return;
        }

        DB::transaction(function () use ($persona) {
            $persona->tickets()->delete();
            $persona->sessions()->delete();
        });

        $this->message = '';
        $this->loadThread();
        $this->notice = 'Chat dan tiket simulasi berhasil dihapus.';

        $this->dispatch('notify', type: 'success', message: 'Chat simulasi direset.');
    }

    public function loadThread(): void
    {
        $this->notice = null;
        $persona = $this->resolvePersona();

        if (! $persona) {
            $this->thread = [];

            return;
        }

        $session = $persona->sessions()->latest('id')->first();

        $this->thread = $session
            ? $session->messages()->orderBy('id')->get()->map(fn (Message $m) => [
                'sender' => $m->sender,
                'body' => $m->body,
                'time' => $m->created_at?->timezone(config('app.display_timezone'))?->format('d/m H:i'),
                'delivery_status' => $m->delivery_status,
            ])->all()
            : [];
    }

    private function resolvePersona(): ?Employee
    {
        if ($this->employeeId === '') {
            return null;
        }

        return Employee::simulated()->find($this->employeeId);
    }

    public function render()
    {
        return view('livewire.wa.simulator', [
            'personas' => Employee::simulated()->orderBy('id')->get(['id', 'name', 'phone']),
            'persona' => $this->resolvePersona(),
        ]);
    }
}
