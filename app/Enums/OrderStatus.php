<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Processing => 'En préparation',
            self::Shipped => 'En livraison',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed, self::Processing => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled, self::Refunded => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Confirmed => 'heroicon-o-check-badge',
            self::Processing => 'heroicon-o-cube',
            self::Shipped => 'heroicon-o-truck',
            self::Delivered => 'heroicon-o-home',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Refunded => 'heroicon-o-arrow-uturn-left',
        };
    }

    /** Étapes affichées dans le suivi client. */
    public static function timeline(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }

    /** Statuts qui comptent dans le chiffre d'affaires. */
    public static function revenueStatuses(): array
    {
        return [self::Confirmed->value, self::Processing->value, self::Shipped->value, self::Delivered->value];
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Refunded]);
    }
}
