<?php

namespace App\Providers\Utils;

use App\Utils\API;
use Illuminate\Support\ServiceProvider;

class ApiProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('api_utils', API::class);
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
