<?php

namespace App\Support;

class MauricioDraft
{
    public function __construct(
        public readonly array $characteristics,
        public readonly array $positiveTagIds,
        public readonly array $negativeTagIds,
        public readonly string $comment,
    ) {
    }
}
