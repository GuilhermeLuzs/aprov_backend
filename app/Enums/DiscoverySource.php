<?php

namespace App\Enums;

enum DiscoverySource: string
{
    case Friend = 'friend';
    case Social = 'social';
    case Partner = 'partner';
    case Ad = 'ad';
    case Search = 'search';
    case Other = 'other';
}
