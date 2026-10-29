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
}
