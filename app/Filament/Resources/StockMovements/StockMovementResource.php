<?php

namespace App\Filament\Resources\StockMovements;

use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Mouvements de stock';

    protected static ?string $modelLabel = 'mouvement de stock';

    protected static ?string $pluralModelLabel = 'mouvements de stock';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('product.name')->label('Produit')->searchable()->description(fn (StockMovement $record) => $record->variant?->name),
                TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => StockMovement::TYPES[$state] ?? $state)
                    ->color(fn ($state) => ['restock' => 'success', 'sale' => 'gray', 'return' => 'info', 'adjustment' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('quantity')->label('Quantité')->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')->weight('bold'),
                TextColumn::make('stock_after')->label('Stock après'),
                TextColumn::make('reason')->label('Motif')->placeholder('—')->wrap(),
                TextColumn::make('user.name')->label('Par')->placeholder('Boutique'),
            ])
            ->filters([
                SelectFilter::make('type')->options(StockMovement::TYPES),
                SelectFilter::make('product')->label('Produit')->relationship('product', 'name')->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockMovements::route('/')];
    }
}
