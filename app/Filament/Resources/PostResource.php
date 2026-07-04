<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Like;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Network Nodes';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'content';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transmission')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Author Node')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('alignment')
                        ->options([
                            'left'    => 'Left',
                            'center'  => 'Center',
                            'right'   => 'Right',
                            'justify' => 'Justify',
                        ])
                        ->default('left'),

                    Forms\Components\Textarea::make('content')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('media_url')
                        ->label('Media URL')
                        ->url()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Retransmission')
                ->description('Only fill this for repost records')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('is_retransmission')
                        ->label('Is Retransmission (Repost)')
                        ->default(false),

                    Forms\Components\Select::make('original_post_id')
                        ->label('Original Post')
                        ->relationship('originalPost', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} — " . \Illuminate\Support\Str::limit($record->content, 60))
                        ->searchable()
                        ->nullable(),
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

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_retransmission')
                    ->label('Repost')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus'),

                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->counts('likes')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comments')
                    ->counts('comments')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('alignment')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_retransmission')
                    ->label('Type')
                    ->trueLabel('Reposts only')
                    ->falseLabel('Original posts only')
                    ->native(false),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Filter by Author')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('alignment')
                    ->options([
                        'left'    => 'Left',
                        'center'  => 'Center',
                        'right'   => 'Right',
                        'justify' => 'Justify',
                    ]),
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
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view'   => Pages\ViewPost::route('/{record}'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['likes', 'comments']);
    }
}
