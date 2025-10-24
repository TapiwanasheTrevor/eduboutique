<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Setting Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this setting (e.g., store_name, whatsapp_number)'),

                        Forms\Components\Select::make('group')
                            ->label('Group')
                            ->options([
                                'general' => 'General',
                                'contact' => 'Contact Information',
                                'social_media' => 'Social Media',
                                'business_hours' => 'Business Hours',
                                'delivery' => 'Delivery Information',
                                'payment' => 'Payment Methods',
                                'odoo' => 'Odoo Configuration',
                                'email' => 'Email Settings',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('general'),

                        Forms\Components\Select::make('type')
                            ->label('Value Type')
                            ->options([
                                'string' => 'String',
                                'text' => 'Text',
                                'number' => 'Number',
                                'boolean' => 'Boolean',
                                'json' => 'JSON',
                                'url' => 'URL',
                                'email' => 'Email',
                            ])
                            ->required()
                            ->default('string')
                            ->live(),
                    ])->columns(3),

                Forms\Components\Section::make('Setting Value')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->maxLength(255)
                            ->visible(fn ($get) => in_array($get('type'), ['string', 'number', 'url', 'email'])),

                        Forms\Components\Textarea::make('value')
                            ->label('Value')
                            ->rows(4)
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'json'])),

                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->formatStateUsing(fn ($state) => $state === 'true' || $state === '1')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'true' : 'false'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'contact' => 'info',
                        'social_media' => 'success',
                        'business_hours' => 'warning',
                        'delivery' => 'primary',
                        'payment' => 'success',
                        'odoo' => 'danger',
                        'email' => 'info',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Key copied'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->type === 'boolean'
                            ? ($state === 'true' ? 'Yes' : 'No')
                            : $state
                    ),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('group', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->options([
                        'general' => 'General',
                        'contact' => 'Contact Information',
                        'social_media' => 'Social Media',
                        'business_hours' => 'Business Hours',
                        'delivery' => 'Delivery Information',
                        'payment' => 'Payment Methods',
                        'odoo' => 'Odoo Configuration',
                        'email' => 'Email Settings',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'text' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                        'url' => 'URL',
                        'email' => 'Email',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Group')
                    ->collapsible(),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
