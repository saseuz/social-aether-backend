<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Network Activity';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $unread = static::getModel()::where('is_read', false)->count();
        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Notification Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Recipient')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('sender_id')
                        ->label('Sender')
                        ->relationship('sender', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Leave blank for system notifications'),

                    Forms\Components\Select::make('type')
                        ->options([
                            'like'    => '❤️ Like',
                            'repost'  => '🔁 Repost',
                            'comment' => '💬 Comment',
                            'reply'   => '↩️ Reply',
                            'follow'  => '👤 Follow',
                            'system'  => '⚡ System',
                        ])
                        ->required(),

                    Forms\Components\Toggle::make('is_read')
                        ->label('Marked as Read')
                        ->default(false),

                    Forms\Components\Select::make('post_id')
                        ->label('Related Post')
                        ->relationship('post', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} — " . \Illuminate\Support\Str::limit($record->content, 60))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Select::make('comment_id')
                        ->label('Related Comment')
                        ->relationship('comment', 'id')
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

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'like'    => 'danger',
                        'repost'  => 'success',
                        'comment' => 'info',
                        'reply'   => 'info',
                        'follow'  => 'primary',
                        'system'  => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'like'    => '❤️ Like',
                        'repost'  => '🔁 Repost',
                        'comment' => '💬 Comment',
                        'reply'   => '↩️ Reply',
                        'follow'  => '👤 Follow',
                        'system'  => '⚡ System',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sender')
                    ->searchable()
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('post.content')
                    ->label('Post')
                    ->limit(50)
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'like'    => '❤️ Like',
                        'repost'  => '🔁 Repost',
                        'comment' => '💬 Comment',
                        'reply'   => '↩️ Reply',
                        'follow'  => '👤 Follow',
                        'system'  => '⚡ System',
                    ]),

                TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->native(false),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Recipient')
                    ->searchable()
                    ->preload(),
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
            'index'  => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'view'   => Pages\ViewNotification::route('/{record}'),
            'edit'   => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
