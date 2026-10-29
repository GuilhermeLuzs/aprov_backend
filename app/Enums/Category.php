<?php

namespace App\Enums;

enum Category: string
{
    case Electronics = 'eletronicos';
    case Food = 'alimentacao';
    case Fashion = 'moda';
    case HomeServices = 'servicos_domesticos';
    case Beauty = 'beleza';
    case Education = 'educacao';
    case Home = 'casa';
    case Health = 'saude';
}
