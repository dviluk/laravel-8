<?php

namespace App\Providers\CurrentUser;

use Illuminate\Support\Facades\Facade;

/**
 * Facade para el control de acceso.
 * 
 * @package App\Providers\CurrentUser
 */
class CurrentUserFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'current_user';
    }
}
