<?php

namespace App\Enums;

enum ReviewCharacteristic: string
{
    case Delivery = 'entrega';
    case Quality = 'qualidade';
    case Conformity = 'conformidade';
    case ValueForMoney = 'custo_beneficio';
}
