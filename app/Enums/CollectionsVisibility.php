<?php

namespace App\Enums;

enum CollectionsVisibility: string
{
    case Public = 'public';
    case Followers = 'followers';
    case Private = 'private';
}
