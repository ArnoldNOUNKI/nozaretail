<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Vide les caches vitrine dès qu'un contenu change dans l'admin
        foreach ([\App\Models\Product::class, Category::class, \App\Models\Banner::class, \App\Models\Setting::class] as $model) {
            $flush = fn () => Cache::deleteMultiple(['home', 'nav_categories']);
            $model::saved($flush);
            $model::deleted($flush);
        }

        View::composer('components.layouts.shop', function ($view) {
            $view->with('navCategories', Cache::remember('nav_categories', 600, fn () => Category::active()->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)])->orderBy('sort')->get()));
        });
    }
}
