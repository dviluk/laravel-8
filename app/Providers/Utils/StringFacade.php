<?php

namespace App\Providers\Utils;

use Illuminate\Support\Facades\Facade;

class StringFacade extends Facade
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
        return 'string_utils';
    }
}
