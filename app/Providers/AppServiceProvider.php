<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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
        // Log user logins
        $this->app['events']->listen(Login::class, function (Login $event) {
            ActivityLogger::login($event->user);
        });

        // Log user logouts
        $this->app['events']->listen(Logout::class, function (Logout $event) {
            ActivityLogger::logout($event->user);
        });
    }
}
