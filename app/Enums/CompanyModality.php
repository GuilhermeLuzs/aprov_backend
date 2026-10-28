<?php

namespace App\Enums;

enum CompanyModality: string
{
    case Physical = 'physical';
    case Online = 'online';
    case Hybrid = 'hybrid';
}
