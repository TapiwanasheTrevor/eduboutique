<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobPostingResource\Pages;
use App\Models\JobPosting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosting::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Job Postings';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label('Job Description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('requirements')
                            ->label('Requirements / Qualifications')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Job Details')
                    ->schema([
                        Forms\Components\TextInput::make('department')
                            ->maxLength(255)
                            ->placeholder('e.g., Marketing, Sales, Operations'),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->default('Harare, Zimbabwe')
                            ->placeholder('e.g., Harare, Zimbabwe'),

                        Forms\Components\Select::make('employment_type')
                            ->required()
                            ->options([
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'Contract',
                                'internship' => 'Internship',
                            ])
                            ->default('full_time'),

                        Forms\Components\Select::make('experience_level')
                            ->options([
                                'entry' => 'Entry Level',
                                'mid' => 'Mid Level',
                                'senior' => 'Senior Level',
                                'lead' => 'Lead / Manager',
                            ])
                            ->placeholder('Select experience level'),
                    ])->columns(2),

                Forms\Components\Section::make('Salary (Optional)')
                    ->schema([
                        Forms\Components\TextInput::make('salary_min')
                            ->label('Minimum Salary')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('salary_max')
                            ->label('Maximum Salary')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\Select::make('salary_currency')
                            ->options([
                                'USD' => 'USD',
                                'ZWL' => 'ZWL',
                            ])
                            ->default('USD'),
                    ])->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Active jobs are visible on the website'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->default(now())
                            ->helperText('When should this job be published?'),

                        Forms\Components\DateTimePicker::make('deadline')
                            ->label('Application Deadline')
                            ->helperText('Optional: When applications close'),
                    ])->columns(3),

                Forms\Components\Section::make('Odoo Sync')
                    ->schema([
                        Forms\Components\TextInput::make('odoo_job_id')
                            ->label('Odoo Job ID')
                            ->numeric()
                            ->disabled()
                            ->helperText('Auto-filled when synced from Odoo'),

                        Forms\Components\DateTimePicker::make('odoo_synced_at')
                            ->label('Last Synced')
                            ->disabled()
                            ->helperText('When this job was last synced with Odoo'),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('employment_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full_time' => 'success',
                        'part_time' => 'info',
                        'contract' => 'warning',
                        'internship' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date()
                    ->sortable()
                    ->placeholder('No deadline')
                    ->color(fn ($record) => $record->deadline && $record->deadline < now() ? 'danger' : null),

                Tables\Columns\IconColumn::make('odoo_job_id')
                    ->label('Odoo')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->odoo_job_id))
                    ->trueIcon('heroicon-o-cloud')
                    ->falseIcon('heroicon-o-computer-desktop')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->odoo_job_id ? 'Synced from Odoo' : 'Created locally'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract' => 'Contract',
                        'internship' => 'Internship',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All jobs')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('from_odoo')
                    ->label('Source')
                    ->placeholder('All sources')
                    ->trueLabel('From Odoo')
                    ->falseLabel('Created locally')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('odoo_job_id'),
                        false: fn ($query) => $query->whereNull('odoo_job_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->color('danger'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPostings::route('/'),
            'create' => Pages\CreateJobPosting::route('/create'),
            'edit' => Pages\EditJobPosting::route('/{record}/edit'),
        ];
    }
}
