<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $p = $this->record;
        if ($p->variants()->exists()) {
            $p->update(['stock' => $p->variants()->sum('stock')]);
        }
        if ($p->track_stock && $p->stock > 0) {
            StockMovement::create(['product_id' => $p->id, 'user_id' => auth()->id(), 'quantity' => $p->stock,
                'type' => 'restock', 'reason' => 'Stock initial', 'stock_after' => $p->stock]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
