<?php

namespace App\Enums;

enum ServiceRecurrence: string
{
    case Once = 'once';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
