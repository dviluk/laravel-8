<?php

namespace App\Providers\Utils;

use App\Utils\FileUtils;
use Illuminate\Support\ServiceProvider;

class FilesProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('files_utils', FileUtils::class);
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
