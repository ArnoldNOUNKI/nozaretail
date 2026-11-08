<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'Noza Retail',
        'tagline' => 'Shop smart. Live better.',
        'phone' => '+237 6 00 00 00 00',
        'whatsapp' => '237600000000',
        'email' => 'contact@nozaretail.cm',
        'address' => 'Akwa, Douala, Cameroun',
        'facebook' => '',
        'instagram' => '',
        'tiktok' => '',
        'announcement' => 'Livraison en 24 h à Douala et Yaoundé · Paiement MTN MoMo & Orange Money',
        'low_stock_email' => '',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings');
    }
}
