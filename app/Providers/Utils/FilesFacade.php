<?php

namespace App\Providers\Utils;

use Illuminate\Support\Facades\Facade;

class FilesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'files_utils';
    }
}
