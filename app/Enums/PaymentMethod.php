<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case MobileMoney = 'mobile_money';
    case CashOnDelivery = 'cod';

    public function getLabel(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::CashOnDelivery => 'Paiement à la livraison',
        };
    }
}
