<?php

namespace App\Providers\CurrentUser;

use Illuminate\Support\ServiceProvider;

class CurrentUserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('current_user', CurrentUser::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
