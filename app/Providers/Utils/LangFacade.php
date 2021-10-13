<?php

namespace App\Providers\Utils;

use Illuminate\Support\Facades\Facade;

class LangFacade extends Facade
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
        return 'lang_utils';
    }
}
