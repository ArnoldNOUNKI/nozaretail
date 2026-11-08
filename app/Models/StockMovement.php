<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = [
        'restock' => 'Réapprovisionnement',
        'sale' => 'Vente',
        'return' => 'Retour / annulation',
        'adjustment' => 'Ajustement inventaire',
    ];

    protected $fillable = ['product_id', 'product_variant_id', 'order_id', 'user_id', 'quantity', 'type', 'reason', 'stock_after'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
