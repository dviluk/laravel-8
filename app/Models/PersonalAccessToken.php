<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use Uuid;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
