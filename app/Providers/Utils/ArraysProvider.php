<?php

namespace App\Providers\Utils;

use App\Utils\ArrayUtils;
use Illuminate\Support\ServiceProvider;

class ArraysProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('arrays_utils', ArrayUtils::class);
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
