<?php

namespace App\Enums;

enum ServiceModality: string
{
    case Remote = 'remote';
    case OnSite = 'on_site';
    case AtHome = 'at_home';
}
