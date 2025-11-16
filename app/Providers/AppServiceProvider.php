<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
		// Grant all abilities to users with the 'Admin' role
		Gate::before(function ($user, string $ability) {
			// Return true to grant, null to defer to normal checks
			return method_exists($user, 'hasRole') && $user->hasRole('Admin') ? true : null;
		});
    }
}
