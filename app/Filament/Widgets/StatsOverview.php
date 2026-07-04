<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers        = User::count();
        $totalPosts        = Post::where('is_retransmission', false)->count();
        $totalReposts      = Post::where('is_retransmission', true)->count();
        $totalComments     = Comment::count();
        $totalNotifications = Notification::count();
        $unreadNotifications = Notification::where('is_read', false)->count();

        // Users joined this week
        $newUsersThisWeek = User::where('created_at', '>=', now()->subWeek())->count();
        // Posts this week
        $postsThisWeek = Post::where('is_retransmission', false)
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($newUsersThisWeek . ' joined this week')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Transmissions', $totalPosts)
                ->description($postsThisWeek . ' posted this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-right'),

            Stat::make('Retransmissions', $totalReposts)
                ->description('Reposts across the network')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Comments', $totalComments)
                ->description('Replies & top-level comments')
                ->descriptionIcon('heroicon-m-chat-bubble-oval-left')
                ->color('warning')
                ->icon('heroicon-o-chat-bubble-oval-left'),

            Stat::make('Notifications', $totalNotifications)
                ->description($unreadNotifications . ' unread')
                ->descriptionIcon($unreadNotifications > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-bell')
                ->color($unreadNotifications > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-bell'),
        ];
    }
}
