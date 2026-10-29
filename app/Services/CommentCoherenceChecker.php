<?php

namespace App\Services;

class CommentCoherenceChecker
{
    public function __construct(private readonly GroqClient $groq)
    {
    }

    public function isCoherent(string $comment, array $tagLabels): bool
    {
        if ($tagLabels === []) {
            return false;
        }

        $answer = $this->groq->chat([
            [
                'role' => 'system',
                'content' => 'Você julga se um comentário de avaliação de produto ou serviço fala sobre os pontos marcados pelo cliente. Responda só "sim" ou "não".',
            ],
            [
                'role' => 'user',
                'content' => "Pontos marcados: ".implode(', ', $tagLabels)."\nComentário: \"{$comment}\"\n\nO comentário fala sobre pelo menos um desses pontos?",
            ],
        ]);

        return $answer !== null && str_starts_with(mb_strtolower($answer), 'sim');
    }
}
