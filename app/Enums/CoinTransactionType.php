<?php

namespace App\Enums;

enum CoinTransactionType: string
{
    case Earned = 'earned';
    case Spent = 'spent';
}
