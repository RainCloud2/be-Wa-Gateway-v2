<?php

namespace Tests\Feature\Wa;

use App\Http\Controllers\Api\Wa\StatusController;
use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Message;
use App\Services\Wa\IncomingMessageProcessor;
use App\Services\Wa\WhatsAppDriver;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-gateway-token';

    protected function setUp(): void
    {
        parent::setUp();
        (new SettingsSeeder)->run();

        config(['wa.driver' => 'gateway']);
        $this->app->forgetInstance(WhatsAppDriver::class);
    }

    private function incoming(array $overrides = [], string $token = self::TOKEN)
    {
        return $this->postJson('/api/wa/incoming', array_merge([
            'wa_message_id' => 'MSG-1',
            'from' => '628123456789',
            'push_name' => 'Budi',
            'type' => 'text',
            'text' => 'printer saya error',
            'timestamp' => 1760000000,
            'image_base64' => null,
            'image_mime' => null,
        ], $overrides), ['X-Gateway-Token' => $token]);
    }

    private function fakeGateways(): void
    {
        Http::fake([
            'gateway.test/send' => Http::response(['status' => 'queued'], 202),
            'gateway.test/status' => Http::response(['wa_status' => 'open', 'connected' => true], 200),
            'agent' => Http::response(['status' => 'queued'], 202),
        ]);
    }

    public function test_incoming_rejects_wrong_token(): void
    {
        Employee::create(['phone' => '628123456789', 'name' => 'Budi']);

        $this->incoming([], 'wrong-token')->assertStatus(401);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_incoming_requires_token_header(): void
    {
        $this->postJson('/api/wa/incoming', ['from' => '628123456789', 'type' => 'text'])
            ->assertStatus(401);
    }

    public function test_status_endpoint_requires_token_and_persists(): void
    {
        $this->postJson('/api/wa/status', ['status' => 'open'])->assertStatus(401);

        $this->postJson('/api/wa/status', [
            'status' => 'qr',
            'qr' => 'abc',
            'phone' => '6281',
        ], ['X-Gateway-Token' => self::TOKEN])->assertOk();

        $this->assertSame('qr', cache(StatusController::CACHE_KEY)['status'] ?? null);
    }

    public function test_duplicate_wa_message_id_is_ignored(): void
    {
        Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $this->fakeGateways();

        $this->incoming(['wa_message_id' => 'DUP-1'])->assertOk()->assertJson(['status' => 'queued']);
        $this->incoming(['wa_message_id' => 'DUP-1'])->assertOk();

        $this->assertSame(1, Message::where('wa_message_id', 'DUP-1')->count());
    }

    public function test_unregistered_number_is_silently_ignored(): void
    {
        $this->fakeGateways();

        $this->incoming(['from' => '628999999999', 'wa_message_id' => 'U-1'])->assertOk();
        $this->incoming(['from' => '628999999999', 'wa_message_id' => 'U-2'])->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gateway.test/send'));
        $this->assertSame(0, Message::count());
        $this->assertSame(0, ChatSession::count());
    }

    public function test_inactive_employee_is_silently_ignored(): void
    {
        Employee::create(['phone' => '628123456789', 'name' => 'Budi', 'is_active' => false]);
        $this->fakeGateways();

        $this->incoming(['wa_message_id' => 'I-1'])->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gateway.test/send'));
        $this->assertSame(0, Message::count());
    }

    public function test_whitelist_endpoint_requires_token_and_returns_only_active_numbers(): void
    {
        Employee::create(['phone' => '628111111111', 'name' => 'Aktif']);
        Employee::create(['phone' => '628222222222', 'name' => 'Nonaktif', 'is_active' => false]);
        Employee::create(['phone' => '628333333333', 'name' => 'Dihapus'])->delete();
        Employee::create(['phone' => '000000000001', 'name' => 'Simulasi', 'is_simulated' => true]);

        $this->getJson('/api/wa/whitelist')->assertStatus(401);

        $this->getJson('/api/wa/whitelist', ['X-Gateway-Token' => self::TOKEN])
            ->assertOk()
            ->assertJson(['numbers' => ['628111111111']]);
    }

    public function test_simulated_number_is_ignored_when_message_comes_from_gateway(): void
    {
        Employee::create(['phone' => '000000000001', 'name' => 'Simulasi', 'is_simulated' => true]);
        $this->fakeGateways();

        $this->incoming(['from' => '000000000001', 'wa_message_id' => 'SIM-API-1'])->assertOk();

        $this->assertSame(0, Message::count());
        $this->assertSame(0, ChatSession::count());
    }

    public function test_other_message_type_gets_canned_reply_without_ai(): void
    {
        Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $this->fakeGateways();

        $this->incoming(['type' => 'other', 'text' => '', 'wa_message_id' => 'O-1'])->assertOk();

        $this->assertDatabaseHas('messages', [
            'sender' => 'ai',
            'body' => IncomingMessageProcessor::NON_TEXT_REPLY,
        ]);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
    }
}
