<?php

namespace App\Utils\API;

/**
 * Internal Server Error
 * 
 * @package App\Utils\API
 */
class Error500 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 500;

    public function __construct(array $extra = [], string $message = null, string $response = 'Internal Server Error')
    {
        parent::__construct($extra, $message, $response);
    }
}
