<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) =>
                                $set('slug', Str::slug($state))
                            ),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price_zwl')
                            ->label('Price (ZWL)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(0.00),

                        Forms\Components\TextInput::make('price_usd')
                            ->label('Price (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(0.00),
                    ])->columns(2),

                Forms\Components\Section::make('Educational Details')
                    ->schema([
                        Forms\Components\Select::make('syllabus')
                            ->options([
                                'ZIMSEC' => 'ZIMSEC',
                                'Cambridge' => 'Cambridge',
                                'Other' => 'Other',
                            ])
                            ->required()
                            ->default('Other'),

                        Forms\Components\Select::make('level')
                            ->options([
                                'ECD' => 'ECD',
                                'Primary' => 'Primary',
                                'Grade 7' => 'Grade 7',
                                'O-Level' => 'O-Level',
                                'A-Level' => 'A-Level',
                                'IGCSE' => 'IGCSE',
                                'AS-Level' => 'AS-Level',
                            ])
                            ->required()
                            ->default('Primary'),

                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(100),
                    ])->columns(3),

                Forms\Components\Section::make('Publishing Details')
                    ->schema([
                        Forms\Components\TextInput::make('publisher')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('author')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('isbn')
                            ->label('ISBN')
                            ->maxLength(50),
                    ])->columns(3),

                Forms\Components\Section::make('Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state > 10) {
                                    $set('stock_status', 'in_stock');
                                } elseif ($state > 0) {
                                    $set('stock_status', 'low_stock');
                                } else {
                                    $set('stock_status', 'out_of_stock');
                                }
                            }),

                        Forms\Components\Select::make('stock_status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock',
                            ])
                            ->required()
                            ->default('in_stock'),

                        Forms\Components\Toggle::make('featured')
                            ->label('Featured Product')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->directory('products')
                            ->imageEditor()
                            ->required(),
                    ])->columns(1),

                Forms\Components\Section::make('Odoo Integration')
                    ->schema([
                        Forms\Components\TextInput::make('odoo_product_id')
                            ->label('Odoo Product ID')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('odoo_synced_at')
                            ->label('Last Synced')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->hidden(fn ($record) => !$record?->odoo_product_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->square()
                    ->size(60),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('syllabus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ZIMSEC' => 'success',
                        'Cambridge' => 'info',
                        'Other' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_usd')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->sortable()
                    ->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('stock_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\IconColumn::make('featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('odoo_product_id')
                    ->label('Synced')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('syllabus')
                    ->options([
                        'ZIMSEC' => 'ZIMSEC',
                        'Cambridge' => 'Cambridge',
                        'Other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'ECD' => 'ECD',
                        'Primary' => 'Primary',
                        'Grade 7' => 'Grade 7',
                        'O-Level' => 'O-Level',
                        'A-Level' => 'A-Level',
                        'IGCSE' => 'IGCSE',
                        'AS-Level' => 'AS-Level',
                    ]),

                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ]),

                Tables\Filters\TernaryFilter::make('featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('sync_to_odoo')
                    ->label('Sync to Odoo')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Product $record) {
                        try {
                            dispatch(new \App\Jobs\SyncProductToOdoo($record));

                            \Filament\Notifications\Notification::make()
                                ->title('Product sync initiated')
                                ->body('The product is being synced to Odoo in the background.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync failed')
                                ->body('Failed to start sync: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('sync_to_odoo')
                        ->label('Sync to Odoo')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Collection $records) {
                            try {
                                $records->each(fn ($record) => dispatch(new \App\Jobs\SyncProductToOdoo($record)));

                                \Filament\Notifications\Notification::make()
                                    ->title('Bulk sync initiated')
                                    ->body($records->count() . ' product(s) are being synced to Odoo in the background.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Bulk sync failed')
                                    ->body('Failed to start bulk sync: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->color('warning')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
