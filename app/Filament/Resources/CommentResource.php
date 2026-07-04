<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left';

    protected static ?string $navigationGroup = 'Network Nodes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comment Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Author')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('post_id')
                        ->label('Post')
                        ->relationship('post', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} — " . \Illuminate\Support\Str::limit($record->content, 60))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('parent_id')
                        ->label('Reply To (Parent Comment)')
                        ->relationship('parent', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} — " . \Illuminate\Support\Str::limit($record->content, 60))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Textarea::make('content')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('post.content')
                    ->label('On Post')
                    ->limit(50)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Comment')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('parent_id')
                    ->label('Reply')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-uturn-left')
                    ->falseIcon('heroicon-o-minus')
                    ->getStateUsing(fn ($record) => $record->parent_id !== null),

                Tables\Columns\TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Filter by Author')
                    ->searchable()
                    ->preload(),

                Filter::make('top_level')
                    ->label('Top-level only')
                    ->query(fn (Builder $query) => $query->whereNull('parent_id')),

                Filter::make('replies_only')
                    ->label('Replies only')
                    ->query(fn (Builder $query) => $query->whereNotNull('parent_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'view'   => Pages\ViewComment::route('/{record}'),
            'edit'   => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('replies');
    }
}
