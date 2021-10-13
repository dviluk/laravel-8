<?php

namespace App\Utils\API;

/**
 * Not Found
 * 
 * @package App\Utils\API
 */
class Error404 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 404;

    public function __construct(array $extra = [], string $message = null, string $response = 'Not Found')
    {
        parent::__construct($extra, $message, $response);
    }
}
