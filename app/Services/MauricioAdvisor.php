<?php

namespace App\Services;

use App\Models\Item;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;

class MauricioAdvisor
{
    private const TOPIC_LABEL = [
        'completed_review' => 'completar a avaliação',
        'min_tags' => 'marcar tags',
        'sent_video' => 'mandar vídeo',
        'min_valid_photos' => 'mandar fotos',
        'min_comment_length' => 'escrever um comentário mais longo',
        'coherent_comment' => 'escrever sobre o que você marcou nas tags',
    ];

    private const REWARD_KEYWORDS = ['moeda', 'ganh', 'ponto', 'xp', 'recompensa', 'nivel', 'nível'];

    private const FALLBACK = 'Não consegui pensar agora, mas posso te ajudar com o comentário, as recompensas ou as fotos. Tenta de novo em instantes.';

    public function __construct(private readonly GroqClient $groq)
    {
    }

    public function reply(Item $item, CoinRuleTopicFacts $facts, array $messages): string
    {
        $instructions = 'Você é o Maurício, o robô mascote simpático da APROV, um app de avaliações. '
            .'Ajuda o cliente a escrever uma avaliação melhor. '
            .'Responda curto, em português do Brasil, no máximo duas frases, sem emoji.';

        if ($this->asksAboutRewards($messages)) {
            $instructions .= ' '.$this->rewardInstructions($item, $facts);
        } else {
            $instructions .= ' Não fale de moedas, pontos APROV ou XP agora, a não ser que o cliente pergunte sobre isso.';
        }

        $groqMessages = [['role' => 'system', 'content' => $instructions]];

        foreach ($messages as $message) {
            $groqMessages[] = [
                'role' => $message['author'] === 'user' ? 'user' : 'assistant',
                'content' => $message['text'],
            ];
        }

        return $this->groq->chat($groqMessages) ?? self::FALLBACK;
    }

    private function rewardInstructions(Item $item, CoinRuleTopicFacts $facts): string
    {
        $item->loadMissing('company.coinRuleTopics');

        $topics = $item->company?->coinRuleTopics ?? collect();

        $missing = $topics
            ->reject(fn ($topic) => CoinRuleTopicEvaluator::satisfies($topic->topic, $topic->threshold, $facts))
            ->map(fn ($topic) => self::TOPIC_LABEL[$topic->topic->value]." (+{$topic->coins} moedas, +{$topic->points} pontos APROV, +{$topic->xp} XP)")
            ->values();

        $rewardContext = $missing->isEmpty()
            ? 'O rascunho já cumpre tudo que rende recompensa nesta empresa.'
            : "O que falta pra render mais nesta avaliação: ".$missing->implode('; ').'.';

        return 'O cliente perguntou sobre moedas, pontos ou XP, então pode falar sobre isso agora. '
            .'Liste cada ação separada da outra, cada uma com o próprio valor entre parênteses, nunca junte o valor de ações diferentes numa frase só. '
            .'Nunca invente valor de recompensa: use só os números a seguir. '.$rewardContext;
    }

    private function asksAboutRewards(array $messages): bool
    {
        $last = end($messages);

        if (! $last) {
            return false;
        }

        $text = mb_strtolower($last['text']);

        foreach (self::REWARD_KEYWORDS as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
