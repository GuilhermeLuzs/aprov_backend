<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Company = 'company';
    case Master = 'master';
}
