<?php

namespace App\Filament\Resources\ShippingZones;

use App\Models\ShippingZone;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Zones de livraison';

    protected static ?string $modelLabel = 'zone de livraison';

    protected static ?string $pluralModelLabel = 'zones de livraison';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom de la zone')->required(),
            TextInput::make('delivery_delay')->label('Délai annoncé')->required()->default('24 à 48 h'),
            TagsInput::make('cities')->label('Villes')->required()->columnSpanFull()
                ->helperText('Tapez une ville puis Entrée. « * » = toutes les autres villes.'),
            TextInput::make('fee')->label('Frais de livraison')->numeric()->required()->suffix('FCFA'),
            TextInput::make('free_above')->label('Gratuite dès')->numeric()->suffix('FCFA')->placeholder('Jamais'),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            Toggle::make('allows_cash_on_delivery')->label('Paiement à la livraison accepté')->default(true)->inline(false),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Zone')->weight('bold')->description(fn (ShippingZone $record) => $record->delivery_delay),
                TextColumn::make('cities')->label('Villes')->badge()->limitList(4)->expandableLimitedList(),
                TextColumn::make('fee')->label('Frais')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('free_above')->label('Offerte dès')->formatStateUsing(fn ($state) => $state ? money($state) : '—'),
                ToggleColumn::make('allows_cash_on_delivery')->label('Paiement livraison'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageShippingZones::route('/')];
    }
}
