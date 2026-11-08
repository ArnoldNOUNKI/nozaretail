<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Transactions Mobile Money';

    protected static ?string $modelLabel = 'transaction';

    protected static ?string $pluralModelLabel = 'transactions Mobile Money';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(['reference', 'external_reference'])->copyable()
                    ->description(fn (Payment $record) => $record->external_reference),
                TextColumn::make('order.number')->label('Commande')
                    ->url(fn (Payment $record) => OrderResource::getUrl('view', ['record' => $record->order])),
                TextColumn::make('amount')->label('Montant')->formatStateUsing(fn ($state) => money($state))->sortable(),
                TextColumn::make('operator')->label('Opérateur')->badge()->color(fn ($state) => $state === 'MTN' ? 'warning' : 'gray')->placeholder('—'),
                TextColumn::make('phone')->label('Numéro')->placeholder('—'),
                TextColumn::make('gateway')->label('Passerelle')->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('status')->label('Statut')->badge()
                    ->color(fn ($state) => ['success' => 'success', 'failed' => 'danger'][$state] ?? 'warning')
                    ->formatStateUsing(fn ($state) => ['success' => 'Réussi', 'failed' => 'Échoué', 'pending' => 'En attente'][$state] ?? $state)
                    ->description(fn (Payment $record) => $record->failure_reason),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué']),
                SelectFilter::make('operator')->label('Opérateur')->options(['MTN' => 'MTN MoMo', 'ORANGE' => 'Orange Money']),
                SelectFilter::make('gateway')->label('Passerelle')->options(['notchpay' => 'Notch Pay', 'campay' => 'CamPay', 'sandbox' => 'Simulateur']),
            ])
            ->recordActions([
                Action::make('refresh')->label('Revérifier')->icon('heroicon-o-arrow-path')->visible(fn (Payment $record) => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $p = app(PaymentService::class)->refresh($record);
                        Notification::make()->title('Statut : '.$p->status)->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/')];
    }
}
