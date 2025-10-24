<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OdooSyncLogResource\Pages;
use App\Models\OdooSyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OdooSyncLogResource extends Resource
{
    protected static ?string $model = OdooSyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Odoo Sync Logs';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sync Details')
                    ->schema([
                        Forms\Components\TextInput::make('model')
                            ->label('Odoo Model')
                            ->disabled(),

                        Forms\Components\TextInput::make('record_id')
                            ->label('Record ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('operation')
                            ->disabled(),

                        Forms\Components\TextInput::make('direction')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('synced_at')
                            ->label('Synced At')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Request Data')
                    ->schema([
                        Forms\Components\Textarea::make('request_data')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->rows(10)
                            ->disabled()
                            ->columnSpanFull(),
                    ])->collapsed(),

                Forms\Components\Section::make('Response Data')
                    ->schema([
                        Forms\Components\Textarea::make('response_data')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->rows(10)
                            ->disabled()
                            ->columnSpanFull(),
                    ])->collapsed(),

                Forms\Components\Section::make('Error Details')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->hidden(fn ($record) => !$record?->error_message),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Odoo Model')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('operation')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'info',
                        'delete' => 'danger',
                        'read' => 'gray',
                        'search' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'to_odoo' => 'info',
                        'from_odoo' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'to_odoo' => 'To Odoo',
                        'from_odoo' => 'From Odoo',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'success' => 'heroicon-o-check-circle',
                        'error' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('record_id')
                    ->label('Record ID')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Synced At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->synced_at?->format('Y-m-d H:i:s')),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->toggleable()
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model')
                    ->options([
                        'product.product' => 'Product',
                        'product.category' => 'Category',
                        'sale.order' => 'Sales Order',
                        'res.partner' => 'Partner',
                    ]),

                Tables\Filters\SelectFilter::make('operation')
                    ->options([
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'read' => 'Read',
                        'search' => 'Search',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'error' => 'Error',
                        'pending' => 'Pending',
                    ]),

                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'to_odoo' => 'To Odoo',
                        'from_odoo' => 'From Odoo',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('synced_at', 'desc')
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOdooSyncLogs::route('/'),
            'view' => Pages\ViewOdooSyncLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
