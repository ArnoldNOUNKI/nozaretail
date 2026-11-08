<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'produit';

    protected static ?string $pluralModelLabel = 'produits';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'brand.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $low = Product::active()->lowStock()->count();

        return $low ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Produits en stock faible';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Informations')->schema([
                    TextInput::make('name')->label('Nom')->required()->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')->label('URL')->required()->unique(ignoreRecord: true)->prefix('/produit/'),
                    Textarea::make('short_description')->label('Accroche')->rows(2)->maxLength(500)
                        ->helperText('Affichée sous le prix sur la fiche produit.'),
                    RichEditor::make('description')->label('Description détaillée')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo']),
                ]),
                Section::make('Photos')->schema([
                    FileUpload::make('images')->hiddenLabel()->image()->multiple()->reorderable()->appendFiles()
                        ->disk('public')->directory('products')->maxFiles(8)->maxSize(4096)
                        ->imageEditor()->imageResizeMode('contain')->imageResizeTargetWidth('1600')
                        ->panelLayout('grid')
                        ->helperText('La première photo est la photo principale. Format portrait 4:5 conseillé, 8 photos maximum.'),
                ]),
                Tabs::make()->tabs([
                    Tab::make('Variantes')->icon('heroicon-o-squares-2x2')->schema([
                        Repeater::make('variants')->relationship()->hiddenLabel()
                            ->schema([
                                KeyValue::make('options')->label('Options')->keyLabel('Option (ex : Taille)')->valueLabel('Valeur (ex : M)')
                                    ->default(['Taille' => ''])->columnSpan(2)->required(),
                                TextInput::make('price')->label('Prix spécifique')->numeric()->suffix('FCFA')->placeholder('Prix du produit'),
                                TextInput::make('stock')->numeric()->default(0)->required(),
                                TextInput::make('sku')->label('Référence')->placeholder('Générée automatiquement'),
                                Toggle::make('is_active')->label('Disponible')->default(true)->inline(false),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state) => collect($state['options'] ?? [])->filter()->join(' / ') ?: 'Nouvelle variante')
                            ->collapsible()->cloneable()
                            ->addActionLabel('Ajouter une variante')
                            ->helperText('Laissez vide pour un produit sans option. Avec des variantes, le stock du produit est la somme des variantes.'),
                    ]),
                    Tab::make('Caractéristiques')->icon('heroicon-o-list-bullet')->schema([
                        KeyValue::make('specs')->hiddenLabel()->keyLabel('Caractéristique')->valueLabel('Valeur')->addActionLabel('Ajouter'),
                        TagsInput::make('tags')->label('Mots-clés de recherche')->separator(','),
                    ]),
                    Tab::make('Référencement')->icon('heroicon-o-magnifying-glass')->schema([
                        TextInput::make('meta_title')->label('Titre SEO')->maxLength(70)->helperText('70 caractères maximum. Par défaut : nom du produit.'),
                        Textarea::make('meta_description')->label('Description SEO')->maxLength(300)->rows(3),
                    ]),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Visibilité')->schema([
                    Toggle::make('is_active')->label('En ligne')->default(true),
                    Toggle::make('is_featured')->label('Mis en avant sur l\'accueil'),
                    Select::make('badge')->label('Badge')->options([
                        'Nouveau' => 'Nouveau', 'Fait au Cameroun' => 'Fait au Cameroun', 'Exclusif' => 'Exclusif', 'Best-seller' => 'Best-seller',
                    ])->placeholder('Aucun'),
                ]),
                Section::make('Prix')->schema([
                    TextInput::make('price')->label('Prix de vente')->numeric()->required()->minValue(0)->suffix('FCFA')->live(onBlur: true),
                    TextInput::make('compare_price')->label('Prix barré')->numeric()->suffix('FCFA')->live(onBlur: true)
                        ->gt('price')->validationMessages(['gt' => 'Le prix barré doit être supérieur au prix de vente.']),
                    TextInput::make('cost')->label('Coût d\'achat')->numeric()->suffix('FCFA')->live(onBlur: true)
                        ->helperText('Visible uniquement par l\'équipe.'),
                    Placeholder::make('margin')->label('Marge')->content(function (Get $get) {
                        $p = (int) $get('price');
                        $c = (int) $get('cost');
                        if (! $p || ! $c) {
                            return '—';
                        }

                        return money($p - $c).' ('.round(($p - $c) / $p * 100).' %)';
                    }),
                    DateTimePicker::make('sale_ends_at')->label('Fin de la promotion')->native(false)
                        ->helperText('Affiche un compte à rebours sur la boutique.'),
                ]),
                Section::make('Stock')->schema([
                    Toggle::make('track_stock')->label('Suivre le stock')->default(true)->live(),
                    TextInput::make('stock')->label('Quantité')->numeric()->default(0)
                        ->visible(fn (Get $get) => $get('track_stock'))
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated(fn (string $operation) => $operation === 'create')
                        ->helperText(fn (string $operation) => $operation === 'edit' ? 'Utilisez « Ajuster le stock » pour tracer les mouvements.' : null),
                    TextInput::make('low_stock_threshold')->label('Alerte stock faible à')->numeric()->default(5)
                        ->visible(fn (Get $get) => $get('track_stock')),
                    TextInput::make('sku')->label('Référence (SKU)')->unique(ignoreRecord: true)->placeholder('Générée automatiquement'),
                    TextInput::make('weight_grams')->label('Poids')->numeric()->suffix('g'),
                ]),
                Section::make('Classement')->schema([
                    Select::make('category_id')->label('Catégorie')->required()->searchable()->preload()
                        ->options(fn () => Category::with('parent')->orderBy('name')->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->parent ? $c->parent->name.' › '.$c->name : $c->name])),
                    Select::make('brand_id')->label('Marque')->relationship('brand', 'name')->searchable()->preload()
                        ->createOptionForm([TextInput::make('name')->label('Nom')->required()]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('images')->label('')->limit(1)->disk('public')->square()->imageSize(48)
                    ->defaultImageUrl(asset('brand/icon-512.png')),
                TextColumn::make('name')->label('Produit')->searchable(['name', 'sku'])->sortable()->wrap()
                    ->description(fn (Product $record) => $record->sku),
                TextColumn::make('category.name')->label('Catégorie')->toggleable(),
                TextColumn::make('brand.name')->label('Marque')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')->label('Prix')->sortable()->formatStateUsing(fn ($state) => money($state))
                    ->description(fn (Product $record) => $record->isOnSale() ? money($record->compare_price).' · -'.$record->discountPercent().' %' : null),
                TextColumn::make('stock')->sortable()->badge()
                    ->color(fn (Product $record) => ! $record->track_stock ? 'gray' : ($record->stock <= 0 ? 'danger' : ($record->stock <= $record->low_stock_threshold ? 'warning' : 'success')))
                    ->formatStateUsing(fn ($state, Product $record) => $record->track_stock ? $state : '∞'),
                TextColumn::make('sales_count')->label('Ventes')->sortable()->numeric(),
                TextColumn::make('rating_avg')->label('Note')->sortable()
                    ->formatStateUsing(fn ($state, Product $record) => $record->rating_count ? number_format($state, 1, ',', '').' ★ ('.$record->rating_count.')' : '—')
                    ->toggleable(),
                ToggleColumn::make('is_active')->label('En ligne'),
                ToggleColumn::make('is_featured')->label('Vedette')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Modifié')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Catégorie')->relationship('category', 'name')->searchable()->preload()->multiple(),
                SelectFilter::make('brand')->label('Marque')->relationship('brand', 'name')->searchable()->preload(),
                TernaryFilter::make('is_active')->label('En ligne'),
                Filter::make('low_stock')->label('Stock faible ou épuisé')->toggle()->query(fn (Builder $query) => $query->lowStock()),
                Filter::make('on_sale')->label('En promotion')->toggle()->query(fn (Builder $query) => $query->onSale()),
                TrashedFilter::make()->label('Corbeille'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    self::adjustStockAction(),
                    Action::make('view_shop')->label('Voir sur la boutique')->icon('heroicon-o-eye')
                        ->url(fn (Product $record) => route('product.show', $record), shouldOpenInNewTab: true),
                    ReplicateAction::make()->label('Dupliquer')
                        ->excludeAttributes(['slug', 'sku', 'sales_count', 'views', 'rating_avg', 'rating_count'])
                        ->beforeReplicaSaved(fn (Product $replica) => $replica->fill(['name' => $replica->name.' (copie)', 'is_active' => false, 'stock' => 0])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Mettre en ligne')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->label('Retirer de la vente')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('discount')->label('Appliquer une remise')->icon('heroicon-o-receipt-percent')
                        ->schema([
                            TextInput::make('percent')->label('Remise')->numeric()->minValue(1)->maxValue(90)->suffix('%')->required(),
                            DateTimePicker::make('ends_at')->label('Jusqu\'au')->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $p) {
                                $base = $p->compare_price ?: $p->price;
                                $p->update(['compare_price' => $base, 'price' => (int) round($base * (100 - $data['percent']) / 100, -2), 'sale_ends_at' => $data['ends_at']]);
                            }
                            Notification::make()->title('Remise appliquée à '.$records->count().' produit(s)')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('end_discount')->label('Arrêter la promotion')->icon('heroicon-o-x-mark')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records->filter->isOnSale() as $p) {
                                $p->update(['price' => $p->compare_price, 'compare_price' => null, 'sale_ends_at' => null]);
                            }
                        })->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function adjustStockAction(): Action
    {
        return Action::make('adjust_stock')->label('Ajuster le stock')->icon('heroicon-o-arrows-up-down')
            ->visible(fn (Product $record) => $record->track_stock)
            ->modalDescription(fn (Product $record) => 'Stock actuel : '.$record->stock)
            ->schema(fn (Product $record) => [
                Select::make('variant_id')->label('Variante')->visible($record->variants()->exists())->required($record->variants()->exists())
                    ->options($record->variants()->get()->mapWithKeys(fn ($v) => [$v->id => $v->name.' — stock '.$v->stock])),
                Select::make('type')->label('Type de mouvement')->required()->default('restock')
                    ->options(collect(StockMovement::TYPES)->except('sale')),
                TextInput::make('quantity')->label('Quantité')->numeric()->required()
                    ->helperText('Positive pour une entrée, négative pour une sortie (casse, perte, inventaire).'),
                TextInput::make('reason')->label('Motif')->placeholder('Ex : arrivage fournisseur du 12/11'),
            ])
            ->action(function (Product $record, array $data) {
                $variant = isset($data['variant_id']) ? $record->variants()->find($data['variant_id']) : null;
                app(InventoryService::class)->move($record, $variant, (int) $data['quantity'], $data['type'], $data['reason'] ?? null);
                Notification::make()->title('Stock mis à jour')->body('Nouveau stock : '.($variant?->fresh()->stock ?? $record->fresh()->stock))->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
