<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_subtotal', 'max_discount', 'max_uses',
        'max_uses_per_user', 'used_count', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(fn (Coupon $c) => $c->code = strtoupper(trim($c->code)));
    }

    /** Retourne un message d'erreur, ou null si le code est utilisable. */
    public function validationError(int $subtotal, ?User $user = null): ?string
    {
        if (! $this->is_active) {
            return 'Ce code promo n\'est plus actif.';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Ce code promo n\'est pas encore valable.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Ce code promo a expiré.';
        }
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return 'Ce code promo a atteint sa limite d\'utilisation.';
        }
        if ($subtotal < $this->min_subtotal) {
            return 'Ce code s\'applique dès '.money($this->min_subtotal).' d\'achat.';
        }
        if ($user && $this->max_uses_per_user) {
            $count = Order::where('user_id', $user->id)->where('coupon_code', $this->code)->count();
            if ($count >= $this->max_uses_per_user) {
                return 'Vous avez déjà utilisé ce code.';
            }
        }

        return null;
    }

    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            'percent' => (int) round($subtotal * $this->value / 100),
            'fixed' => (int) $this->value,
            default => 0,
        };
        if ($this->max_discount) {
            $discount = min($discount, (int) $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    public function label(): string
    {
        return match ($this->type) {
            'percent' => '-'.$this->value.' %',
            'fixed' => '-'.money($this->value),
            'free_shipping' => 'Livraison offerte',
        };
    }
}
