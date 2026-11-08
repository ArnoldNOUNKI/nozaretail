<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'shipping_zone_id', 'customer_name', 'customer_phone', 'customer_email',
        'city', 'district', 'landmark', 'notes', 'subtotal', 'discount', 'shipping_fee', 'total',
        'coupon_code', 'status', 'payment_status', 'payment_method', 'tracking_token',
        'paid_at', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $o) {
            $o->number = $o->number ?: 'NZ'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $o->tracking_token = $o->tracking_token ?: Str::random(32);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function margin(): int
    {
        return (int) $this->items->sum(fn ($i) => ($i->unit_price - ($i->unit_cost ?? $i->unit_price)) * $i->quantity) - $this->discount;
    }
}
