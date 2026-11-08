<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['product_id', 'user_id', 'author_name', 'rating', 'title', 'body', 'is_verified_purchase', 'is_approved'];

    protected $casts = ['is_approved' => 'boolean', 'is_verified_purchase' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn (Review $r) => $r->product?->refreshRating());
        static::deleted(fn (Review $r) => $r->product?->refreshRating());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
