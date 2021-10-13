<?php

namespace App\Utils\API;

/**
 * Forbidden
 * 
 * @package App\Utils\API
 */
class Error403 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 403;

    public function __construct(array $extra = [], string $message = null, string $response = 'Forbidden')
    {
        parent::__construct($extra, $message, $response);
    }
}
