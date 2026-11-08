<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $with = ['brand', 'category'];

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'compare_price', 'cost', 'stock', 'low_stock_threshold', 'track_stock',
        'images', 'specs', 'tags', 'badge', 'weight_grams', 'is_active', 'is_featured', 'sale_ends_at',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'images' => 'array',
        'specs' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_stock' => 'boolean',
        'sale_ends_at' => 'datetime',
        'rating_avg' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            $p->slug = $p->slug ?: Str::slug($p->name).'-'.Str::lower(Str::random(4));
            $p->sku = $p->sku ?: 'NZ-'.Str::upper(Str::random(8));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOnSale(Builder $q): Builder
    {
        return $q->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price');
    }

    public function scopeLowStock(Builder $q): Builder
    {
        return $q->where('track_stock', true)->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function isOnSale(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->isOnSale() ? (int) round(100 - ($this->price / $this->compare_price * 100)) : 0;
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants') ? $this->variants->isNotEmpty() : $this->variants()->exists();
    }

    public function totalStock(): int
    {
        return $this->hasVariants() ? (int) $this->variants->where('is_active', true)->sum('stock') : (int) $this->stock;
    }

    public function inStock(): bool
    {
        return ! $this->track_stock || $this->totalStock() > 0;
    }

    public function imageUrl(int $index = 0): ?string
    {
        $path = $this->images[$index] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function imageUrls(): array
    {
        return collect($this->images ?? [])->map(fn ($p) => Storage::disk('public')->url($p))->all();
    }

    /** Options regroupées pour le sélecteur : ["Couleur" => ["Noir","Blanc"], "Taille" => [...]] */
    public function optionGroups(): array
    {
        $groups = [];
        foreach ($this->variants->where('is_active', true) as $v) {
            foreach ($v->options ?? [] as $k => $val) {
                $groups[$k][$val] = $val;
            }
        }

        return array_map('array_values', $groups);
    }

    public function refreshRating(): void
    {
        $approved = $this->reviews()->where('is_approved', true);
        $this->forceFill([
            'rating_avg' => round((float) $approved->avg('rating'), 2),
            'rating_count' => $approved->count(),
        ])->saveQuietly();
    }
}
