<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Company = 'company';
    case Master = 'master';
}
