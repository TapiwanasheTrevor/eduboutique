<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Services\OdooService;
use App\Services\CustomerSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mobile')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'individual' => 'Individual',
                                'company' => 'Company',
                            ])
                            ->required()
                            ->default('individual'),
                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('job_title')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('street')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('street2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255)
                            ->default('Zimbabwe'),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->options([
                                'website' => 'Website',
                                'inquiry' => 'Inquiry',
                                'odoo' => 'Odoo',
                                'manual' => 'Manual',
                            ])
                            ->required()
                            ->default('manual'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Odoo Sync')
                    ->schema([
                        Forms\Components\TextInput::make('odoo_partner_id')
                            ->label('Odoo Partner ID')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('odoo_synced_at')
                            ->label('Last Synced')
                            ->disabled(),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'individual',
                        'warning' => 'company',
                    ]),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'success' => 'website',
                        'info' => 'inquiry',
                        'warning' => 'odoo',
                        'gray' => 'manual',
                    ]),
                Tables\Columns\IconColumn::make('odoo_partner_id')
                    ->label('Odoo Synced')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->odoo_partner_id))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'individual' => 'Individual',
                        'company' => 'Company',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'website' => 'Website',
                        'inquiry' => 'Inquiry',
                        'odoo' => 'Odoo',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\TernaryFilter::make('odoo_synced')
                    ->label('Odoo Synced')
                    ->placeholder('All')
                    ->trueLabel('Synced')
                    ->falseLabel('Not Synced')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('odoo_partner_id'),
                        false: fn (Builder $query) => $query->whereNull('odoo_partner_id'),
                    ),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('syncToOdoo')
                    ->label('Sync to Odoo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn ($record) => is_null($record->odoo_partner_id))
                    ->action(function ($record) {
                        try {
                            $odoo = app(OdooService::class);
                            $syncService = new CustomerSyncService($odoo);
                            $syncService->pushCustomerToOdoo($record);

                            Notification::make()
                                ->title('Customer synced to Odoo')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('syncToOdoo')
                        ->label('Sync to Odoo')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->action(function ($records) {
                            $synced = 0;
                            $errors = 0;

                            try {
                                $odoo = app(OdooService::class);
                                $syncService = new CustomerSyncService($odoo);

                                foreach ($records as $record) {
                                    if (is_null($record->odoo_partner_id)) {
                                        try {
                                            $syncService->pushCustomerToOdoo($record);
                                            $synced++;
                                        } catch (\Exception $e) {
                                            $errors++;
                                        }
                                    }
                                }

                                Notification::make()
                                    ->title("Synced {$synced} customers" . ($errors > 0 ? " ({$errors} errors)" : ''))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InquiriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
