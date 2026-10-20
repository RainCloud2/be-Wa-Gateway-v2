<?php

namespace App\Http\Controllers\Api\Wa;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomingController extends Controller
{
    /**
     * Accept an incoming WhatsApp message from the gateway.
     *
     * Responds immediately and processes the message through the queue so the
     * gateway never has to wait (it retries on failure).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'wa_message_id' => ['nullable', 'string', 'max:255'],
            'from' => ['required', 'string', 'max:20'],
            'push_name' => ['nullable', 'string', 'max:120'],
            'type' => ['required', 'in:text,image,other'],
            'text' => ['nullable', 'string', 'max:4000'],
            'timestamp' => ['nullable', 'integer'],
            'image_base64' => ['nullable', 'string'],
            'image_mime' => ['nullable', 'string', 'max:100'],
        ]);

        ProcessIncomingMessage::dispatch($data);

        return response()->json(['status' => 'queued']);
    }
}
