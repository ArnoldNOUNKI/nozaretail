<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'logo', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(fn (Brand $b) => $b->slug = $b->slug ?: Str::slug($b->name));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
