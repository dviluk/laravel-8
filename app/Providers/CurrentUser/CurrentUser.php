<?php

namespace App\Providers\CurrentUser;

class CurrentUser
{
    /**
     * Instancia del usuario autenticado.
     * 
     * @var \App\User
     */
    private $currentUser;

    /**
     * 
     * @return void 
     */
    public function __construct($userId = null)
    {
        $this->currentUser = $userId ? \App\Models\User::find($userId) : app('auth')->user();
    }

    /**
     * Retorna el usuario autenticado.
     *
     * @return \App\Models\User
     */
    public function get()
    {
        return $this->currentUser;
    }

    /**
     * Retorna el Id del usuario autenticado.
     * 
     * @return int 
     */
    public function id(): int
    {
        return $this->currentUser->id;
    }
}
