<?php

namespace App\Jobs;

use App\Services\Wa\AiTurnService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAiReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * @param  array{mime: string, data: string}|null  $image
     */
    public function __construct(
        public int $sessionId,
        public ?int $messageId = null,
        public ?array $image = null,
    ) {}

    public function handle(AiTurnService $ai): void
    {
        $ai->handle($this->sessionId, $this->messageId, $this->image);
    }
}
