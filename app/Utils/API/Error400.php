<?php

namespace App\Utils\API;

/**
 * Bad Request
 * 
 * @package App\Utils\API
 */
class Error400 extends ErrorResponse
{
    /**
     * Estado de la respuesta.
     *
     * @var integer
     */
    protected $status = 400;

    public function __construct(array $extra = [], string $message = null, string $response = 'Bad Request')
    {
        parent::__construct($extra, $message, $response);
    }
}
