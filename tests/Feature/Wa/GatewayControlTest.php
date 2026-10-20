<?php

namespace Tests\Feature\Wa;

use App\Livewire\Cms\Dashboard;
use App\Livewire\Wa\GatewayControl;
use App\Livewire\Wa\WhatsAppStatus;
use App\Models\User;
use App\Services\Wa\GatewayProcessManager;
use App\Services\Wa\WhatsAppDriver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class GatewayControlTest extends TestCase
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

    private function bindManager(array $statuses, array $actions = []): void
    {
        $manager = Mockery::mock(GatewayProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(true);
        $manager->shouldReceive('status')->andReturn(...$statuses);
        $manager->shouldReceive('health')->zeroOrMoreTimes()->andReturn(['ok' => false, 'status' => null]);

        foreach ($actions as $action => $result) {
            $manager->shouldReceive($action)->once()->andReturn($result);
        }

        $this->app->instance(GatewayProcessManager::class, $manager);
    }

    public function test_admin_can_start_the_gateway(): void
    {
        $admin = $this->user('admin');

        $this->bindManager([
            ['available' => true, 'running' => false, 'pid' => null, 'message' => 'Gateway berhenti.'],
            ['available' => true, 'running' => true, 'pid' => 1234, 'message' => 'Gateway berjalan.'],
        ], [
            'start' => ['ok' => true, 'message' => 'Gateway berhasil dijalankan.'],
        ]);

        Livewire::actingAs($admin)
            ->test(GatewayControl::class)
            ->assertSee('Nyalakan')
            ->call('start')
            ->assertDispatched('gateway-process-changed')
            ->assertDispatched('notify')
            ->assertSee('Berjalan (PID 1234)');
    }

    public function test_admin_can_stop_and_restart_the_gateway(): void
    {
        $admin = $this->user('admin');

        $this->bindManager([
            ['available' => true, 'running' => true, 'pid' => 1234, 'message' => 'Gateway berjalan.'],
            ['available' => true, 'running' => false, 'pid' => null, 'message' => 'Gateway berhenti.'],
            ['available' => true, 'running' => true, 'pid' => 5678, 'message' => 'Gateway berjalan.'],
        ], [
            'stop' => ['ok' => true, 'message' => 'Gateway berhasil dihentikan.'],
            'restart' => ['ok' => true, 'message' => 'Gateway berhasil di-restart.'],
        ]);

        Livewire::actingAs($admin)
            ->test(GatewayControl::class)
            ->call('stop')
            ->assertDispatched('gateway-process-changed')
            ->assertDispatched('notify')
            ->call('restart')
            ->assertDispatched('gateway-process-changed')
            ->assertDispatched('notify');
    }

    public function test_user_without_permission_cannot_control_the_gateway(): void
    {
        $user = $this->user('user');

        $manager = Mockery::mock(GatewayProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(true);
        $manager->shouldReceive('status')->andReturn([
            'available' => true,
            'running' => false,
            'pid' => null,
            'message' => 'Gateway berhenti.',
        ]);
        $manager->shouldNotReceive('start');
        $this->app->instance(GatewayProcessManager::class, $manager);

        Livewire::actingAs($user)
            ->test(GatewayControl::class)
            ->call('start');
    }

    public function test_whatsapp_status_refreshes_when_the_gateway_process_changes(): void
    {
        $admin = $this->user('admin');

        $driver = Mockery::mock(WhatsAppDriver::class);
        $driver->shouldReceive('status')->twice()->andReturn([
            'status' => 'open',
            'qr' => null,
            'phone' => '628123456789',
            'connected' => true,
        ]);
        $this->app->instance(WhatsAppDriver::class, $driver);

        Livewire::actingAs($admin)
            ->test(WhatsAppStatus::class)
            ->assertSet('status.status', 'open')
            ->dispatch('gateway-process-changed')
            ->assertSet('status.status', 'open');
    }

    public function test_dashboard_refreshes_gateway_status_when_the_process_changes(): void
    {
        $admin = $this->user('admin');

        $driver = Mockery::mock(WhatsAppDriver::class);
        $driver->shouldReceive('status')->once()->andReturn([
            'status' => 'open',
            'qr' => null,
            'phone' => '628123456789',
            'connected' => true,
        ]);
        $this->app->instance(WhatsAppDriver::class, $driver);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->dispatch('gateway-process-changed')
            ->assertSet('waStatus.status', 'open');
    }

    public function test_logout_stops_the_gateway_when_process_control_is_enabled(): void
    {
        $admin = $this->user('admin');

        $driver = Mockery::mock(WhatsAppDriver::class);
        $driver->shouldReceive('logout')->once()->andReturn(true);
        $driver->shouldReceive('status')->twice()->andReturn([
            'status' => 'unreachable',
            'qr' => null,
            'phone' => null,
            'connected' => false,
        ]);
        $this->app->instance(WhatsAppDriver::class, $driver);

        $manager = Mockery::mock(GatewayProcessManager::class);
        $manager->shouldReceive('enabled')->atLeast()->once()->andReturn(true);
        $manager->shouldReceive('stop')->once()->andReturn(['ok' => true, 'message' => 'Gateway dihentikan.']);
        $manager->shouldReceive('status')->zeroOrMoreTimes()->andReturn([
            'available' => true,
            'running' => false,
            'pid' => null,
            'message' => 'Gateway berhenti.',
        ]);
        $this->app->instance(GatewayProcessManager::class, $manager);

        Livewire::actingAs($admin)
            ->test(WhatsAppStatus::class)
            ->call('logout')
            ->assertDispatched('gateway-process-changed')
            ->assertDispatched('notify');
    }

    public function test_logout_does_not_stop_the_gateway_when_process_control_is_disabled(): void
    {
        $admin = $this->user('admin');

        $driver = Mockery::mock(WhatsAppDriver::class);
        $driver->shouldReceive('logout')->once()->andReturn(true);
        $driver->shouldReceive('status')->twice()->andReturn([
            'status' => 'logged_out',
            'qr' => null,
            'phone' => null,
            'connected' => false,
        ]);
        $this->app->instance(WhatsAppDriver::class, $driver);

        $manager = Mockery::mock(GatewayProcessManager::class);
        $manager->shouldReceive('enabled')->atLeast()->once()->andReturn(false);
        $manager->shouldNotReceive('stop');
        $manager->shouldReceive('status')->zeroOrMoreTimes()->andReturn([
            'available' => false,
            'running' => false,
            'pid' => null,
            'message' => 'Kontrol proses gateway hanya aktif di lingkungan Windows lokal.',
        ]);
        $this->app->instance(GatewayProcessManager::class, $manager);

        Livewire::actingAs($admin)
            ->test(WhatsAppStatus::class)
            ->call('logout')
            ->assertDispatched('gateway-process-changed')
            ->assertDispatched('notify');
    }

    public function test_whatsapp_status_renders_qr_as_an_image(): void
    {
        $admin = $this->user('admin');

        $driver = Mockery::mock(WhatsAppDriver::class);
        $driver->shouldReceive('status')->once()->andReturn([
            'status' => 'qr',
            'qr' => '2@test-qr-payload',
            'phone' => null,
            'connected' => false,
        ]);
        $this->app->instance(WhatsAppDriver::class, $driver);

        Livewire::actingAs($admin)
            ->test(WhatsAppStatus::class)
            ->assertSet('status.status', 'qr')
            ->assertSee('QR WhatsApp')
            ->assertSeeHtml('data:image/svg+xml;base64,')
            ->assertDontSee('Scan QR di terminal gateway');
    }

    public function test_controls_are_hidden_when_process_control_is_disabled(): void
    {
        $admin = $this->user('admin');

        $manager = Mockery::mock(GatewayProcessManager::class);
        $manager->shouldReceive('enabled')->andReturn(false);
        $manager->shouldReceive('status')->andReturn([
            'available' => false,
            'running' => false,
            'pid' => null,
            'message' => 'Kontrol proses gateway hanya aktif di lingkungan Windows lokal.',
        ]);
        $this->app->instance(GatewayProcessManager::class, $manager);

        Livewire::actingAs($admin)
            ->test(GatewayControl::class)
            ->assertDontSee('Nyalakan')
            ->assertDontSee('Restart')
            ->assertSee('Kontrol proses hanya aktif di lingkungan lokal');
    }
}
