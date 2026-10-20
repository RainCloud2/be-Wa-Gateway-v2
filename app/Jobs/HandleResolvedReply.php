<?php

namespace App\Jobs;

use App\Services\Wa\ResolutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandleResolvedReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $sessionId,
        public ?int $messageId = null,
    ) {}

    public function handle(ResolutionService $resolution): void
    {
        $resolution->handle($this->sessionId, $this->messageId);
    }
}
