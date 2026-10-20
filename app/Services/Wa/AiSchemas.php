<?php

namespace App\Services\Wa;

use App\Models\Ticket;

/**
 * JSON schemas passed to Gemini for structured output.
 */
class AiSchemas
{
    /**
     * Conversational turn schema.
     *
     * @return array<string, mixed>
     */
    public static function turn(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reply' => [
                    'type' => 'string',
                    'description' => 'Jawaban singkat untuk karyawan, langkah bernomor bila memberi solusi.',
                ],
                'category' => [
                    'type' => 'string',
                    'enum' => Ticket::CATEGORIES,
                ],
                'user_intent' => [
                    'type' => 'string',
                    'enum' => ['solved', 'not_solved', 'new_info', 'unclear', 'accept_ticket', 'decline_ticket'],
                ],
                'wants_human' => [
                    'type' => 'boolean',
                ],
            ],
            'required' => ['reply', 'category', 'user_intent', 'wants_human'],
        ];
    }

    /**
     * Ticket metadata schema.
     *
     * @return array<string, mixed>
     */
    public static function ticket(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Judul tiket singkat dan jelas dalam Bahasa Indonesia.',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'Ringkasan: masalah, langkah yang sudah dicoba, dan hasilnya.',
                ],
                'category' => [
                    'type' => 'string',
                    'enum' => Ticket::CATEGORIES,
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => Ticket::priorities(),
                ],
            ],
            'required' => ['title', 'summary', 'category', 'priority'],
        ];
    }

    /**
     * Resolution confirmation schema (used while a ticket is RESOLVED).
     *
     * @return array<string, mixed>
     */
    public static function resolution(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intent' => [
                    'type' => 'string',
                    'enum' => ['solved', 'not_solved'],
                    'description' => 'Apakah masalah karyawan sudah teratasi?',
                ],
            ],
            'required' => ['intent'],
        ];
    }
}
