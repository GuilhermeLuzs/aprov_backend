<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class FullTextSearch
{
    private const MIN_TOKEN_LENGTH = 3;

    private const STOPWORDS = [
        'about', 'are', 'com', 'for', 'from', 'how', 'that', 'the', 'this', 'was', 'what', 'when',
        'where', 'who', 'will', 'with', 'und', 'www',
    ];

    private array $indexedWords = [];

    private array $plainWords = [];

    private function __construct(string $search)
    {
        $words = preg_split('/[^\p{L}\p{N}_]+/u', mb_strtolower(trim($search)), -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_unique($words) as $word) {
            if (mb_strlen($word) >= self::MIN_TOKEN_LENGTH && ! in_array($word, self::STOPWORDS, true)) {
                $this->indexedWords[] = $word;
            } else {
                $this->plainWords[] = $word;
            }
        }
    }

    public static function of(string $search): self
    {
        return new self($search);
    }

    public function isEmpty(): bool
    {
        return $this->indexedWords === [] && $this->plainWords === [];
    }

    public function condition(string $column): array
    {
        $parts = [];
        $bindings = [];

        if ($this->indexedWords !== []) {
            $parts[] = "MATCH({$column}) AGAINST (? IN BOOLEAN MODE)";
            $bindings[] = $this->booleanQuery();
        }

        foreach ($this->plainWords as $word) {
            $parts[] = "{$column} LIKE ?";
            $bindings[] = '%'.addcslashes($word, '%_\\').'%';
        }

        return ['('.implode(' AND ', $parts).')', $bindings];
    }

    public function score(string $column): array
    {
        return ["MATCH({$column}) AGAINST (? IN BOOLEAN MODE)", [$this->booleanQuery()]];
    }

    public function constrain(Builder $query, string $column): Builder
    {
        [$sql, $bindings] = $this->condition($column);

        return $query->whereRaw($sql, $bindings);
    }

    public function orderByRelevance(Builder $query, string $column): Builder
    {
        if ($this->indexedWords === []) {
            return $query;
        }

        [$score, $bindings] = $this->score($column);

        return $query->orderByRaw("{$score} DESC", $bindings);
    }

    public function orderByRelevanceOf(Builder $query, Builder $scoredSubquery): Builder
    {
        return $this->indexedWords === [] ? $query : $query->orderByDesc($scoredSubquery);
    }

    private function booleanQuery(): string
    {
        return implode(' ', array_map(fn (string $word) => "+{$word}*", $this->indexedWords));
    }
}
