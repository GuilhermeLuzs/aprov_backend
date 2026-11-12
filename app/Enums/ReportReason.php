<?php

namespace App\Enums;

enum ReportReason: string
{
    case Offensive = 'offensive';
    case PersonalData = 'personal_data';
    case Spam = 'spam';
    case FalseInfo = 'false_info';
    case Other = 'other';
}
