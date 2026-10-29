<?php

namespace App\Enums;

/**
 * Number of employees.
 */
enum CompanySize: string
{
    case UpTo10 = '1-10';
    case From11To50 = '11-50';
    case From51To200 = '51-200';
    case From201To500 = '201-500';
    case From501To1000 = '501-1000';
    case Over1000 = '1001+';
}
