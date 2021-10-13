<?php

namespace App\Utils\API;

/**
 * Invalid Input
 * 
 * @package App\Utils\API
 */
class Error422 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 422;

    public function __construct(array $extra = [], string $message = null, string $response = 'Invalid Input')
    {
        parent::__construct($extra, $message, $response);
    }
}
