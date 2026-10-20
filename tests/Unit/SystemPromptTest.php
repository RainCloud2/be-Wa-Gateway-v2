<?php

namespace Tests\Unit;

use App\Services\Wa\SystemPrompt;
use Tests\TestCase;

class SystemPromptTest extends TestCase
{
    public function test_default_prompt_forbids_claiming_ticket_creation(): void
    {
        $prompt = SystemPrompt::default();

        $this->assertStringContainsString('JANGAN pernah menulis bahwa tiket sudah dibuat', $prompt);
        $this->assertStringContainsString('accept_ticket', $prompt);
    }
}
