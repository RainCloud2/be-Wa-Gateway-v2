<?php

namespace Tests\Feature\Wa;

use App\Livewire\Wa\QueueWorkerControl;
use App\Models\User;
use App\Services\Wa\QueueWorkerProcessManager;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class QueueWorkerControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new PermissionSeeder)->run();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function workerStatus(bool $running, bool $managed = false, ?int $pid = null): array
    {
        return [
            'available' => true,
            'running' => $running,
            'managed' => $managed,
            'pid' => $pid,
            'heartbeat_at' => $running ? now()->toIso8601String() : null,
            'heartbeat_fresh' => $running,
            'message' => $running ? 'Queue worker aktif.' : 'Queue worker tidak terdeteksi.',
        ];
    }

    private function bindManager(array $statuses, array $actions = []): void
    {
        $manager = Mockery::mock(QueueWorkerProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(true);
        $manager->shouldReceive('status')->andReturn(...$statuses);

        foreach ($actions as $action => $result) {
            $manager->shouldReceive($action)->once()->andReturn($result);
        }

        $this->app->instance(QueueWorkerProcessManager::class, $manager);
    }

    public function test_admin_can_start_the_queue_worker(): void
    {
        $admin = $this->user('admin');

        $this->bindManager([
            $this->workerStatus(false),
            $this->workerStatus(true, true, 1234),
        ], [
            'start' => ['ok' => true, 'message' => 'Queue worker berhasil dijalankan.'],
        ]);

        Livewire::actingAs($admin)
            ->test(QueueWorkerControl::class)
            ->assertSee('Nyalakan Worker')
            ->call('start')
            ->assertDispatched('queue-process-changed')
            ->assertDispatched('notify')
            ->assertSee('Aktif (PID 1234)');
    }

    public function test_admin_can_stop_and_restart_the_queue_worker(): void
    {
        $admin = $this->user('admin');

        $this->bindManager([
            $this->workerStatus(true, true, 1234),
            $this->workerStatus(false),
            $this->workerStatus(true, true, 5678),
        ], [
            'stop' => ['ok' => true, 'message' => 'Queue worker berhasil dihentikan.'],
            'restart' => ['ok' => true, 'message' => 'Queue worker berhasil di-restart.'],
        ]);

        Livewire::actingAs($admin)
            ->test(QueueWorkerControl::class)
            ->call('stop')
            ->assertDispatched('queue-process-changed')
            ->call('restart')
            ->assertDispatched('queue-process-changed');
    }

    public function test_pending_jobs_show_a_warning_when_worker_is_stopped(): void
    {
        $admin = $this->user('admin');

        DB::table((string) config('queue.connections.database.table', 'jobs'))->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->bindManager([$this->workerStatus(false)]);

        Livewire::actingAs($admin)
            ->test(QueueWorkerControl::class)
            ->assertSee('1 pending')
            ->assertSee('queue worker tidak aktif');
    }

    public function test_user_without_permission_cannot_control_the_queue_worker(): void
    {
        $user = $this->user('user');

        $manager = Mockery::mock(QueueWorkerProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(true);
        $manager->shouldReceive('status')->andReturn($this->workerStatus(false));
        $manager->shouldNotReceive('start');
        $this->app->instance(QueueWorkerProcessManager::class, $manager);

        Livewire::actingAs($user)
            ->test(QueueWorkerControl::class)
            ->call('start');
    }

    public function test_controls_are_hidden_when_queue_process_control_is_disabled(): void
    {
        $admin = $this->user('admin');

        $manager = Mockery::mock(QueueWorkerProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(false);
        $manager->shouldReceive('status')->andReturn($this->workerStatus(false));
        $this->app->instance(QueueWorkerProcessManager::class, $manager);

        Livewire::actingAs($admin)
            ->test(QueueWorkerControl::class)
            ->assertDontSee('Nyalakan Worker')
            ->assertSee('Kontrol worker hanya aktif di lingkungan lokal');
    }

    public function test_queue_events_write_and_clear_the_worker_heartbeat(): void
    {
        Cache::forget(QueueWorkerProcessManager::HEARTBEAT_KEY);

        event(new Looping('database', 'default'));

        $this->assertNotNull(Cache::get(QueueWorkerProcessManager::HEARTBEAT_KEY));

        event(new WorkerStopping);

        $this->assertNull(Cache::get(QueueWorkerProcessManager::HEARTBEAT_KEY));
    }
}
