<?php

namespace App\Providers;

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
        // Nama hari/bulan Carbon mengikuti locale aplikasi (translatedFormat).
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));

        // Lonceng notifikasi di navbar: hitung + 7 terbaru untuk user login.
        View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $view->with('notifUnread', $user->unreadNotifications()->count());
                $view->with('notifRecent', $user->notifications()->limit(7)->get());
            } else {
                $view->with('notifUnread', 0);
                $view->with('notifRecent', collect());
            }
        });
    }
}
