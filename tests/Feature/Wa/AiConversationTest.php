<?php

namespace Tests\Feature\Wa;

use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Ticket;
use App\Services\Wa\AiTurnService;
use App\Services\Wa\TicketService;
use App\Services\Wa\WhatsAppDriver;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiConversationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-gateway-token';

    private int $messageSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        (new SettingsSeeder)->run();

        config(['wa.driver' => 'gateway']);
        $this->app->forgetInstance(WhatsAppDriver::class);
    }

    private function employee(): Employee
    {
        return Employee::create(['phone' => '628123456789', 'name' => 'Budi', 'is_active' => true]);
    }

    private function send(string $text): void
    {
        $this->messageSeq++;

        $this->postJson('/api/wa/incoming', [
            'wa_message_id' => 'AI-'.$this->messageSeq,
            'from' => '628123456789',
            'push_name' => 'Budi',
            'type' => 'text',
            'text' => $text,
            'timestamp' => 1760000000 + $this->messageSeq,
        ], ['X-Gateway-Token' => self::TOKEN])->assertOk();
    }

    private function fakeGemini(array $turns, int $status = 200): void
    {
        $sequence = Http::sequence();

        foreach ($turns as $turn) {
            $sequence->push([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($turn)]]]]],
            ], $status);
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => $sequence,
            'gateway.test/*' => Http::response(['status' => 'queued'], 202),
        ]);
    }

    private function turn(string $intent, string $reply = 'Baik.', bool $wantsHuman = false, string $category = 'printer'): array
    {
        return ['reply' => $reply, 'category' => $category, 'user_intent' => $intent, 'wants_human' => $wantsHuman];
    }

    public function test_attempts_accumulate_until_ticket_offer(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('not_solved', 'Coba restart printernya.'),
            $this->turn('not_solved', 'Coba ganti kabelnya.'),
            $this->turn('not_solved', 'Coba install ulang drivernya.'),
        ]);

        $this->send('printer error');
        $this->send('masih error');
        $this->send('belum bisa');

        $session = ChatSession::first();
        $this->assertSame(3, $session->attempt_count);
        $this->assertTrue($session->awaiting_ticket_confirmation);

        $lastAi = Message::where('sender', 'ai')->latest('id')->first();
        $this->assertStringContainsString('tiket', mb_strtolower($lastAi->body));
    }

    public function test_accepting_ticket_creates_ticket_and_switches_to_human(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('accept_ticket', 'Baik, saya buatkan tiketnya.'),
            ['title' => 'Printer error', 'summary' => 'Sudah dicoba restart.', 'category' => 'printer', 'priority' => 'normal'],
        ]);

        $this->send('printer error');
        $this->send('masih error');
        $this->send('belum bisa');
        $this->send('ya tolong buatkan tiket');

        $this->assertDatabaseCount('tickets', 1);
        $ticket = Ticket::first();
        $this->assertSame('TKT-0001', $ticket->code);
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);

        $session = ChatSession::first();
        $this->assertSame(ChatSession::MODE_HUMAN, $session->mode);
        $this->assertSame($ticket->id, $session->ticket_id);
        $this->assertFalse($session->awaiting_ticket_confirmation);
    }

    public function test_declining_ticket_lowers_attempt_count(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('decline_ticket', 'Baik, kita coba lagi.'),
        ]);

        $this->send('printer error');
        $this->send('masih error');
        $this->send('belum bisa');
        $this->send('tidak usah');

        $session = ChatSession::first();
        $this->assertFalse($session->awaiting_ticket_confirmation);
        $this->assertSame(1, $session->attempt_count); // max_attempts (3) - 2
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_keyword_accept_creates_ticket_even_when_ai_intent_is_wrong(): void
    {
        $this->employee();
        // The AI claims it created a ticket and returns a non-accept intent.
        $this->fakeGemini([
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('new_info', 'Baik, saya akan buatkan tiket sekarang. Teknisi akan menghubungi Anda.'),
            ['title' => 'Layar terbalik', 'summary' => 'Sudah dicoba Ctrl+Alt+Panah.', 'category' => 'hardware', 'priority' => 'normal'],
        ]);

        $this->send('layar terbalik');
        $this->send('masih belum bisa');
        $this->send('tetap tidak berubah');
        $this->send('boleh');

        $this->assertDatabaseCount('tickets', 1);
        $session = ChatSession::first();
        $this->assertSame(ChatSession::MODE_HUMAN, $session->mode);
        $this->assertNotNull($session->ticket_id);

        $lastAi = Message::where('sender', 'ai')->latest('id')->first();
        $this->assertStringContainsString('sudah saya buatkan', $lastAi->body);
        $this->assertStringContainsString('TKT-0001', $lastAi->body);
    }

    public function test_keyword_decline_blocks_ticket_even_when_ai_intent_is_wrong(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('not_solved'),
            $this->turn('new_info', 'Oke, silakan dicoba lagi ya.'),
        ]);

        $this->send('printer error');
        $this->send('masih error');
        $this->send('belum bisa');
        $this->send('tidak usah');

        $this->assertDatabaseCount('tickets', 0);
        $session = ChatSession::first();
        $this->assertFalse($session->awaiting_ticket_confirmation);
        $this->assertSame(1, $session->attempt_count);
    }

    public function test_burst_of_messages_produces_a_single_ai_reply(): void
    {
        $employee = $this->employee();
        $this->fakeGemini([
            $this->turn('not_solved', 'Baik, saya bantu ketiganya ya.'),
        ]);

        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_AI,
            'started_at' => now(),
        ]);

        $first = Message::create(['session_id' => $session->id, 'sender' => 'employee', 'body' => 'masalah 1']);
        $second = Message::create(['session_id' => $session->id, 'sender' => 'employee', 'body' => 'masalah 2']);
        $third = Message::create(['session_id' => $session->id, 'sender' => 'employee', 'body' => 'masalah 3']);

        $ai = app(AiTurnService::class);

        // Stale turns are skipped (the message is no longer the latest one).
        $ai->handle($session->id, $first->id);
        $ai->handle($session->id, $second->id);
        $this->assertSame(0, Message::where('sender', 'ai')->count());

        // Only the latest message produces a reply.
        $ai->handle($session->id, $third->id);
        $this->assertSame(1, Message::where('sender', 'ai')->count());
    }

    public function test_ai_self_offer_sets_awaiting_and_next_yes_creates_ticket(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('new_info', 'Baik, apakah Anda ingin saya buatkan tiket bantuan untuk masalah ini?'),
            $this->turn('new_info', 'Baik, saya mengerti.'),
            ['title' => 'Laptop rusak', 'summary' => 'Layar biru.', 'category' => 'hardware', 'priority' => 'normal'],
        ]);

        $this->send('laptop ku rusak');
        $this->assertTrue(ChatSession::first()->awaiting_ticket_confirmation);

        $this->send('ya');

        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame(ChatSession::MODE_HUMAN, ChatSession::first()->mode);
    }

    public function test_explicit_ticket_request_creates_ticket_without_prior_offer(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('new_info', 'Baik, ada hal lain yang bisa saya bantu?'),
            ['title' => 'Minta tiket', 'summary' => 'Karyawan minta tiket.', 'category' => 'other', 'priority' => 'normal'],
        ]);

        $this->send('buka tiket');

        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame(ChatSession::MODE_HUMAN, ChatSession::first()->mode);
    }

    public function test_plain_yes_without_offer_does_not_create_ticket(): void
    {
        $this->employee();
        $this->fakeGemini([
            $this->turn('new_info', 'Apakah laptopnya sudah menyala?'),
            $this->turn('new_info', 'Baik, silakan coba restart.'),
        ]);

        $this->send('laptop rusak');
        $this->send('ya');

        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(ChatSession::MODE_AI, ChatSession::first()->mode);
    }

    public function test_human_mode_does_not_trigger_ai(): void
    {
        $employee = $this->employee();
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);
        Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Printer',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now(),
        ]);
        Http::fake(['gateway.test/*' => Http::response([], 202)]);

        $this->send('apakah sudah ditangani?');

        $this->assertSame(0, Message::where('sender', 'ai')->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
    }

    public function test_resolved_confirmation_closes_ticket_and_returns_to_ai(): void
    {
        $employee = $this->employee();
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);
        $ticket = Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Printer',
            'status' => Ticket::STATUS_RESOLVED,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now(),
            'resolved_at' => now(),
        ]);
        $session->update(['ticket_id' => $ticket->id]);

        $this->fakeGemini([
            ['intent' => 'solved'],
        ]);

        $this->send('sudah bisa, terima kasih');

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $session->refresh();
        $this->assertSame(ChatSession::MODE_AI, $session->mode);
        $this->assertNull($session->ticket_id);

        $closure = Message::where('sender', 'ai')->latest('id')->first();
        $this->assertStringContainsString('kami tutup', $closure->body);
        $this->assertTrue($closure->created_at->lessThanOrEqualTo($ticket->fresh()->closed_at));
    }

    public function test_gemini_failure_triggers_fallback_and_offer(): void
    {
        $this->employee();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('boom', 500),
            'gateway.test/*' => Http::response([], 202),
        ]);

        $this->send('printer error');

        $lastAi = Message::where('sender', 'ai')->latest('id')->first();
        $this->assertNotNull($lastAi);
        $this->assertStringContainsString('gangguan sementara', $lastAi->body);
        $this->assertTrue(ChatSession::first()->awaiting_ticket_confirmation);
    }

    public function test_image_with_caption_is_forwarded_to_gemini_as_multimodal(): void
    {
        $this->employee();
        $this->fakeGemini([$this->turn('new_info', 'Coba cek gambarnya.', false, 'other')]);

        $this->postJson('/api/wa/incoming', [
            'wa_message_id' => 'IMG-1',
            'from' => '628123456789',
            'push_name' => 'Budi',
            'type' => 'image',
            'text' => 'ini foto errornya',
            'timestamp' => 1760000000,
            'image_base64' => 'AAAA',
            'image_mime' => 'image/jpeg',
        ], ['X-Gateway-Token' => self::TOKEN])->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'generativelanguage')) {
                return false;
            }

            foreach ($request['contents'] ?? [] as $content) {
                foreach ($content['parts'] ?? [] as $part) {
                    if (isset($part['inlineData'])) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    public function test_gateway_503_marks_message_failed(): void
    {
        $this->employee();
        config(['wa.outgoing.tries' => 1]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->turn('not_solved'))]]]]],
            ], 200),
            'gateway.test/send' => Http::response(['error' => 'wa_disconnected'], 503),
        ]);

        $this->send('printer error');

        $aiMessage = Message::where('sender', 'ai')->latest('id')->first();
        $this->assertSame(Message::DELIVERY_FAILED, $aiMessage->delivery_status);
    }

    public function test_simulated_ticket_does_not_notify_it_or_send_whatsapp(): void
    {
        Setting::put('it_notify_numbers', ['628999999999']);

        $persona = Employee::createSimulation();
        $session = ChatSession::create([
            'employee_id' => $persona->id,
            'mode' => ChatSession::MODE_AI,
            'started_at' => now(),
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'title' => 'Printer simulasi',
                    'summary' => 'Printer error saat simulasi.',
                    'category' => 'printer',
                    'priority' => 'normal',
                ])]]]]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $ticket = app(TicketService::class)->createFromSession($session, 'printer');

        $this->assertSame($persona->id, $ticket->employee_id);
        $this->assertSame(
            Message::DELIVERY_SENT,
            Message::where('session_id', $session->id)->where('sender', 'ai')->value('delivery_status'),
        );
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gateway.test'));
    }
}
