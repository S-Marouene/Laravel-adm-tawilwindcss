<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Gate;
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
        // The super-admin bypasses every permission check (site settings, user
        // management, dossiers, references…) — he still can't reach records
        // outside his territory since AdminScope only applies to scoped roles.
        Gate::before(function (User $user) {
            return $user->hasRole('super-admin') ? true : null;
        });

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
