<?php

namespace App\Exceptions;

use API;
use App\Utils\API\Error401;
use App\Utils\API\Error422;
use App\Utils\API\ErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ErrorResponse) {
            return API::exceptionResponse($e);
        } else if ($e instanceof ValidationException) {
            $ex = new Error422($e->errors());
            return API::exceptionResponse($ex);
        } else if ($e instanceof AuthenticationException) {
            $ex = new Error401();
            return API::exceptionResponse($ex);
        }
        // TODO HANDLE AUTHENTICATION ERROR CON Error401


        return parent::render($request, $e);
    }
}
