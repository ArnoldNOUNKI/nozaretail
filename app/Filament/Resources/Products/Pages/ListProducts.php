<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouveau produit')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'active' => Tab::make('En ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'low' => Tab::make('Stock faible')->badge(Product::lowStock()->where('stock', '>', 0)->count() ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->lowStock()->where('stock', '>', 0)),
            'out' => Tab::make('Épuisés')->badge(Product::where('track_stock', true)->where('stock', '<=', 0)->count() ?: null)->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('track_stock', true)->where('stock', '<=', 0)),
            'draft' => Tab::make('Hors ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
