<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = ['name', 'cities', 'fee', 'free_above', 'delivery_delay', 'allows_cash_on_delivery', 'is_active', 'sort'];

    protected $casts = ['cities' => 'array', 'is_active' => 'boolean', 'allows_cash_on_delivery' => 'boolean'];

    public function feeFor(int $subtotal): int
    {
        return ($this->free_above && $subtotal >= $this->free_above) ? 0 : (int) $this->fee;
    }

    public static function forCity(string $city): ?self
    {
        $needle = mb_strtolower(trim($city));

        return static::where('is_active', true)->orderBy('sort')->get()
            ->first(fn (self $z) => collect($z->cities)->contains(fn ($c) => mb_strtolower($c) === $needle))
            ?? static::where('is_active', true)->orderByDesc('sort')->get()
                ->first(fn (self $z) => collect($z->cities)->contains('*'));
    }

    /** Toutes les villes desservies, pour le sélecteur du checkout. */
    public static function cityOptions(): array
    {
        return static::where('is_active', true)->orderBy('sort')->get()
            ->flatMap(fn ($z) => $z->cities)->reject(fn ($c) => $c === '*')->unique()->values()->all();
    }
}
