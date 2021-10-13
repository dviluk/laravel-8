<?php

namespace App\Providers\Utils;

use App\Utils\LangUtils;
use Illuminate\Support\ServiceProvider;

class LangProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('lang_utils', LangUtils::class);
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
