<?php

namespace App\Utils\API;

/**
 * Unauthenticated
 * 
 * @package App\Utils\API
 */
class Error401 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 401;

    public function __construct(array $extra = [], string $message = null, string $response = 'Unauthenticated')
    {
        parent::__construct($extra, $message, $response);
    }
}
