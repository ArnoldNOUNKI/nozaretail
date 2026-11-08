<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image', 'is_active', 'is_featured', 'sort'];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(fn (Category $c) => $c->slug = $c->slug ?: Str::slug($c->name));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Ids de la catégorie et de ses sous-catégories (2 niveaux). */
    public function familyIds(): array
    {
        return collect([$this->id])->merge($this->children()->pluck('id'))->all();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
