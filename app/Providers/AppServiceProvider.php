<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share nav badge counts with the dashboard layout
        View::composer(['layouts.dashboard', 'layouts.admin'], function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $clientIds = $user->clients()->pluck('clients.id');

                // Count online visitors for the Visitors badge
                $navVisitorCount = VisitorSession::whereIn('client_id', $clientIds)
                    ->where('is_online', true)
                    ->count();

                // Count chats with unread messages for the Live Chat badge
                $navUnreadChatCount = Chat::whereIn('client_id', $clientIds)
                    ->where('status', '!=', 'closed')
                    ->whereHas('messages', function ($q) {
                        $q->where('is_read', false)
                          ->where('sender_type', 'visitor');
                    })
                    ->count();

                $view->with('navVisitorCount', $navVisitorCount);
                $view->with('navUnreadChatCount', $navUnreadChatCount);
            } else {
                $view->with('navVisitorCount', 0);
                $view->with('navUnreadChatCount', 0);
            }
        });
    }
}
