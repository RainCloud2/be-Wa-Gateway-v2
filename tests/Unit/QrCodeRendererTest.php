<?php

namespace Tests\Unit;

use App\Services\Wa\QrCodeRenderer;
use Tests\TestCase;

class QrCodeRendererTest extends TestCase
{
    public function test_it_renders_qr_as_an_svg_data_uri(): void
    {
        $dataUri = app(QrCodeRenderer::class)->dataUri('https://example.test/pairing?token=abc');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);

        $svg = base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_it_renders_a_long_whatsapp_pairing_payload(): void
    {
        $payload = 'https://wa.me/settings/linked_devices#'.str_repeat('2@zxp/hvA7jhzbE5zYEKxTLkMV02Kp+t9JGzJUBTdqZaRZ0fPnGFF+uaijXLOJCcpZ4ZsLqRD4chFGWE25Unc6NTGYFP3/07ly9FU=', 2);

        $dataUri = app(QrCodeRenderer::class)->dataUri($payload);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }
}
