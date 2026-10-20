<?php

namespace App\Jobs;

use App\Services\Wa\IncomingMessageProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(IncomingMessageProcessor $processor): void
    {
        $processor->process($this->payload);
    }
}
