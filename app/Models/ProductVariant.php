<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'options', 'sku', 'price', 'stock', 'is_active'];

    protected $casts = ['options' => 'array', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $v) {
            $v->sku = $v->sku ?: 'NZV-'.Str::upper(Str::random(8));
            if (! $v->name && $v->options) {
                $v->name = implode(' / ', array_values($v->options));
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function effectivePrice(): int
    {
        return (int) ($this->price ?: $this->product->price);
    }
}
