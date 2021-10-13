<?php

namespace App\Utils\API;

/**
 * Session Expired
 * 
 * @package App\Utils\API
 */
class Error419 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 419;

    public function __construct(array $extra = [], string $message = null, string $response = 'Session Expired')
    {
        parent::__construct($extra, $message, $response);
    }
}
