<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Filament\Resources\VideoResource\RelationManagers;
use App\Models\Video;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Video Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('video_url')
                            ->label('Video URL')
                            ->required()
                            ->url()
                            ->helperText('YouTube or Vimeo URL')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->url()
                            ->helperText('Optional: Custom thumbnail URL')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Categorization')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->options([
                                'study_tips' => 'Study Tips',
                                'book_previews' => 'Book Previews',
                                'syllabus_guides' => 'Syllabus Guides',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('other'),

                        Forms\Components\TextInput::make('duration')
                            ->label('Duration')
                            ->helperText('e.g., 5:30 or 1:23:45')
                            ->maxLength(20)
                            ->placeholder('MM:SS'),
                    ])->columns(2),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Toggle::make('published')
                            ->label('Published')
                            ->default(true)
                            ->helperText('Only published videos will be visible on the website'),

                        Forms\Components\TextInput::make('views')
                            ->label('View Count')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically tracked'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->square()
                    ->size(60)
                    ->defaultImageUrl(fn ($record) => 'https://via.placeholder.com/150?text=No+Thumbnail'),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'study_tips' => 'success',
                        'book_previews' => 'info',
                        'syllabus_guides' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->alignCenter()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('published')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'study_tips' => 'Study Tips',
                        'book_previews' => 'Book Previews',
                        'syllabus_guides' => 'Syllabus Guides',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('published')
                    ->label('Published Status')
                    ->placeholder('All videos')
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['published' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['published' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->color('danger'),
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
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
