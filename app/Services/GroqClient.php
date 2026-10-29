<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqClient
{
    public function chat(array $messages, int $maxTokens = 300): ?string
    {
        $key = config('services.groq.key');

        if (! $key) {
            return null;
        }

        try {
            $response = Http::withToken($key)
                ->timeout(8)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'),
                    'temperature' => 0.4,
                    'max_tokens' => $maxTokens,
                    'reasoning_effort' => 'low',
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::warning('Groq respondeu com erro.', ['status' => $response->status()]);

                return null;
            }

            $content = trim($response->json('choices.0.message.content', ''));

            return $content === '' ? null : $content;
        } catch (\Throwable $e) {
            Log::warning('Falha ao chamar a Groq.', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
