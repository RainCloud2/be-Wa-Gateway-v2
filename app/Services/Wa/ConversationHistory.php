<?php

namespace App\Services\Wa;

use App\Models\ChatSession;

class ConversationHistory
{
    /**
     * Build a compact Gemini history for a session: the most recent
     * employee/AI messages, oldest first. Technician and system messages
     * are excluded.
     *
     * @return array<int, array{role: string, text: string}>
     */
    public static function forSession(ChatSession $session, ?int $limit = null): array
    {
        $limit ??= (int) config('wa.history_limit', 20);

        $messages = $session->messages()
            ->whereIn('sender', ['employee', 'ai'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function ($message) {
            $text = (string) ($message->body ?? '');

            if ($message->media_type === 'image' && $text === '') {
                $text = '[Gambar tanpa keterangan]';
            }

            return [
                'role' => $message->sender === 'ai' ? 'model' : 'user',
                'text' => $text,
            ];
        })->all();
    }
}
