<?php

namespace App\Enums;

enum CoinRuleTopic: string
{
    case CompletedReview = 'completed_review';
    case MinTags = 'min_tags';
    case SentVideo = 'sent_video';
    case MinValidPhotos = 'min_valid_photos';
    case MinCommentLength = 'min_comment_length';
    case CoherentComment = 'coherent_comment';
    case PhotoInUse = 'photo_in_use';

    public function isPayable(): bool
    {
        return $this !== self::PhotoInUse;
    }

    public static function forKind(ItemKind $kind): array
    {
        $skipped = $kind === ItemKind::Service ? [self::SentVideo, self::PhotoInUse] : [];

        return array_values(array_filter(self::cases(), fn (self $topic) => ! in_array($topic, $skipped, true)));
    }
}
