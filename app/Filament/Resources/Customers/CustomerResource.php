<?php

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Clients';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'client';

    protected static ?string $slug = 'clients';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        $paid = OrderStatus::revenueStatuses();

        return parent::getEloquentQuery()->where('is_admin', false)
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($query) => $query->whereIn('status', $paid)], 'total')
            ->withMax('orders as last_order_at', 'created_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Téléphone')->tel(),
            Toggle::make('is_active')->label('Compte actif')->inline(false),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Profil')->columnSpan(1)->schema([
                TextEntry::make('name')->label('Nom')->weight('bold'),
                TextEntry::make('email')->icon('heroicon-o-envelope')->copyable(),
                TextEntry::make('phone')->label('Téléphone')->icon('heroicon-o-phone')->placeholder('—'),
                TextEntry::make('created_at')->label('Client depuis')->date('d/m/Y'),
            ]),
            Section::make('Valeur client')->columnSpan(2)->columns(3)->schema([
                TextEntry::make('orders_count')->label('Commandes'),
                TextEntry::make('spent')->label('Total dépensé')->formatStateUsing(fn ($state) => money($state))->placeholder(money(0)),
                TextEntry::make('avg')->label('Panier moyen')->state(fn (User $record) => $record->orders_count ? money(($record->spent ?? 0) / max(1, $record->orders_count)) : '—'),
                RepeatableEntry::make('addresses')->label('Adresses')->columnSpanFull()->columns(3)->schema([
                    TextEntry::make('label')->hiddenLabel()->weight('bold'),
                    TextEntry::make('district')->hiddenLabel()->formatStateUsing(fn ($state, $record) => $state.', '.$record->city),
                    TextEntry::make('phone')->hiddenLabel(),
                ])->placeholder('Aucune adresse enregistrée'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Client')->searchable(['name', 'email', 'phone'])->sortable()->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label('Téléphone')->toggleable(),
                TextColumn::make('orders_count')->label('Commandes')->sortable()->alignCenter(),
                TextColumn::make('spent')->label('Dépensé')->sortable()->formatStateUsing(fn ($state) => money($state))->placeholder('—'),
                TextColumn::make('last_order_at')->label('Dernière commande')->since()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Inscrit le')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('buyers')->label('Ont déjà commandé')->toggle()->query(fn (Builder $query) => $query->has('orders')),
                Filter::make('vip')->label('Clients fidèles (3+ commandes)')->toggle()->query(fn (Builder $query) => $query->has('orders', '>=', 3)),
                Filter::make('dormant')->label('Inactifs depuis 60 jours')->toggle()
                    ->query(fn (Builder $query) => $query->has('orders')->whereDoesntHave('orders', fn ($o) => $o->where('created_at', '>=', now()->subDays(60)))),
            ])
            ->recordActions([ViewAction::make()->iconButton(), EditAction::make()->iconButton()]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\OrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
