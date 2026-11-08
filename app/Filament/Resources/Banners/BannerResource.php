<?php

namespace App\Filament\Resources\Banners;

use App\Models\Banner;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $modelLabel = 'bannière';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Titre')->required()->maxLength(80),
            TextInput::make('subtitle')->label('Sous-titre')->maxLength(160),
            TextInput::make('cta_label')->label('Texte du bouton'),
            TextInput::make('cta_url')->label('Lien du bouton')->placeholder('/boutique?categorie=mode'),
            Select::make('placement')->label('Emplacement')->required()->default('home_hero')
                ->options(['home_hero' => 'Accueil — carrousel principal', 'home_strip' => 'Accueil — bandeau promotionnel']),
            TextInput::make('sort')->label('Ordre')->numeric()->default(0),
            FileUpload::make('image')->image()->disk('public')->directory('banners')->imageEditor()->columnSpanFull()
                ->helperText('Carrousel : 1600×900 px conseillé. Sans image, la bannière s\'affiche en typographie sur fond noir.'),
            DateTimePicker::make('starts_at')->label('Début')->native(false),
            DateTimePicker::make('ends_at')->label('Fin')->native(false),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->imageHeight(40),
                TextColumn::make('title')->label('Titre')->description(fn (Banner $record) => $record->subtitle)->wrap(),
                TextColumn::make('placement')->label('Emplacement')->badge()->formatStateUsing(fn ($state) => $state === 'home_hero' ? 'Carrousel' : 'Bandeau'),
                TextColumn::make('ends_at')->label('Fin')->dateTime('d/m/Y')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBanners::route('/')];
    }
}
