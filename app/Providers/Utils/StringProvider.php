<?php

namespace App\Providers\Utils;

use App\Utils\StringUtils;
use Illuminate\Support\ServiceProvider;

class StringProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('string_utils', StringUtils::class);
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
