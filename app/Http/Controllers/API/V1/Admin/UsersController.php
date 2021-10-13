<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\CRUDController;
use App\Http\Resources\V1\UserResource;
use App\Repositories\V1\UsersRepository;

class UsersController extends CRUDController
{
    /**
     * Instancia del repositorio.
     * 
     * @var \App\Repositories\V1\UsersRepository
     */
    protected $repo = UsersRepository::class;

    /**
     * @var \App\Http\Resources\V1\UserResource
     */
    protected $resource = UserResource::class;
}
