<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label('Voir sur la boutique')->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('product.show', $this->record), shouldOpenInNewTab: true),
            ProductResource::adjustStockAction()->after(fn () => $this->refreshFormData(['stock'])),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->variants()->exists()) {
            $this->record->update(['stock' => $this->record->variants()->sum('stock')]);
        }
    }
}
