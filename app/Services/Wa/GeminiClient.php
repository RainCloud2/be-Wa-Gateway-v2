<?php

namespace App\Services\Wa;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Gemini generateContent REST endpoint.
 *
 * The model name always comes from config (GEMINI_MODEL) and is never
 * hardcoded. Structured output is requested through responseMimeType +
 * responseSchema so the model returns valid JSON.
 */
class GeminiClient
{
    /**
     * @param  array<int, array{role: string, text: string}>  $history
     * @param  array<string, mixed>  $schema  JSON schema for the structured response.
     * @param  array{mime: string, data: string}|null  $image  Optional inline image.
     * @return array<string, mixed>
     */
    public function generateStructured(string $systemPrompt, array $history, array $schema, ?array $image = null): array
    {
        $key = (string) config('services.gemini.key');
        $model = (string) config('services.gemini.model');

        if ($key === '') {
            throw new GeminiException('Gemini API key is not configured.');
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $this->buildContents($history, $image),
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.4,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $key,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('services.gemini.timeout'))
                ->retry((int) config('services.gemini.retries'), 300, throw: false)
                ->post($this->endpoint($model), $payload);
        } catch (ConnectionException $e) {
            throw new GeminiException('Gemini request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new GeminiException('Gemini returned HTTP '.$response->status());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new GeminiException('Gemini returned an empty response.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new GeminiException('Gemini response was not valid JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array<int, array{role: string, parts: array<int, array<string, mixed>>}>
     */
    private function buildContents(array $history, ?array $image): array
    {
        $contents = [];

        foreach ($history as $index => $item) {
            $role = ($item['role'] ?? 'user') === 'ai' ? 'model' : ($item['role'] ?? 'user');
            $parts = [];

            if ($item['text'] !== '' && $item['text'] !== null) {
                $parts[] = ['text' => (string) $item['text']];
            }

            $isLast = $index === array_key_last($history);

            if ($isLast && $image && $role === 'user') {
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $image['mime'],
                        'data' => $image['data'],
                    ],
                ];
            }

            if ($parts === []) {
                $parts[] = ['text' => ''];
            }

            $contents[] = ['role' => $role, 'parts' => $parts];
        }

        return $contents;
    }

    private function endpoint(string $model): string
    {
        $base = rtrim((string) config('services.gemini.base_url'), '/');

        return $base.'/models/'.$model.':generateContent';
    }
}
