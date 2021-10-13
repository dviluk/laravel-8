<?php

namespace App\Utils;

use App\Utils\API\ErrorResponseInterface;
use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Contiene método útiles para realizar peticiones.
 */
class API
{
    /**
     * La petición ha sido completada correctamente.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response200($extra = [], $message = 'Success')
    {
        return $this->prepareResponse(200, $message, $extra);
    }

    /**
     * Se creó correctamente el recurso.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response201($extra = [], $message = 'Created')
    {
        return $this->prepareResponse(200, $message, $extra);
    }

    /**
     * Los datos de la petición no fueron entendidos por el servidor.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response400($extra = [], $message = 'Bad Request')
    {
        return $this->prepareResponse(400, $message, $extra);
    }

    /**
     * La petición require autenticación del usuario.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response401($extra = [], $message = 'Unauthenticated')
    {
        return $this->prepareResponse(401, $message, $extra);
    }

    /**
     * No tiene permiso para completar la acción de la petición.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response403($extra = [], $message = 'Forbidden')
    {
        return $this->prepareResponse(403, $message, $extra);
    }

    /**
     * El recurso que solicita no se encontró.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response404($extra = [], $message = 'Not Found')
    {
        return $this->prepareResponse(404, $message, $extra);
    }

    /**
     * El recurso que solicita no se encontró.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response422($errors = [], $message = 'Datos incorrectos')
    {
        return $this->prepareResponse(422, $message, [
            'errors' => $errors
        ]);
    }

    /**
     * Ocurrió un error en el servidor durante el procesamiento de la petición.
     * 
     * @param string $message Mensaje descriptivo de la acción realizada
     * @return \Illuminate\Http\JsonResponse
     */
    public function response500($extra = [], $message = 'Internal Server Error')
    {
        return $this->prepareResponse(500, $message, $extra);
    }

    /**
     * Genera una respuesta en base a los parámetros especificados.
     *
     * @param int $code código de la respuesta
     * @param string $status descripción del estado 
     * 
     * Ej: `Success`, `Unauthorized`, etc
     * 
     * @param array $extra
     *
     * @return void
     */
    private function prepareResponse($code, $status, $extra = [])
    {
        $response = [];

        if ($code >= 400) {
            $response['errorMessage'] = $status;
            $response['errorCode'] = $code;
            $response['success'] = false;
        } else {
            $response['success'] = true;
            $response['message'] = $status;
        }

        if (count($extra) > 0) $response = array_merge($response, $extra);

        return $this->json($response, $code);
    }

    /**
     * Retorna una respuesta en formato json.
     * 
     * @param array $response Respuesta JSON
     * @param int $status Código http, default 200
     * 
     * @return \Illuminate\Http\Response
     */
    public function json($response, $status = 200, $headers = [])
    {
        return response()->json($response, $status, $headers);
    }

    /**
     * Undocumented function
     *
     * @param array|Collection $data
     * @param \Closure $formatter
     */
    public function formatResponse($data = [], ?\Closure $formatter = null)
    {
        $formatted = [];

        if ($data === null) {
            return [];
        }

        if (is_array($data) || $data instanceof Collection) { // $data es una lista
            foreach ($data as $item) {
                if (is_null($item) && $item instanceof \Eloquent) {
                    $formatted[] = $item->toArray();
                } else {
                    $formatted[] = $formatter($item);
                }
            }
        } else {
            if (is_null($data) && $data instanceof \Eloquent) {
                $formatted = $data->toArray();
            } else {
                $formatted = $formatter($data); // $data es un array
            }
        }

        return $formatted;
    }

    /**
     * Pagina un listado de recursos.
     *
     * @param Illuminate\Contracts\Pagination\LengthAwarePaginator $data Paginator
     * @param \Closure $formatter función que se encargara del formateo de recursos
     */
    public function paginate(LengthAwarePaginator $data, \Closure $formatter = null, $extra = null)
    {
        $response = [
            'total' => $data->total(),
            'data' => $this->formatResponse($data->items(), $formatter),

            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),

            'next_url' => $data->nextPageUrl(),
            'prev_url' => $data->previousPageUrl(),

            'last_page' => (int) $data->lastPage(),
            'last_url' => $data->url($data->lastPage()),
            'first_url' => $data->url(1),

            'filters' => request()->filters,
        ];

        if ($extra && is_array($extra)) {
            $response['extra'] = $extra;
        }

        return $this->response200($response);
    }

    /**
     * Retorna la excepción en formato JSON y los datos del usuario autenticado.
     *
     * @param \Exception|\Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function exceptionResponse($e)
    {
        if ($e instanceof ErrorResponseInterface) {
            $status = $e->getStatus();
            $extra = $e->getExtra();
            $response = $e->getMessage() ?? $e->getResponse();

            if (request()->ajax()) {
                switch ($status) {
                    case 400:
                        return $this->response400($extra, $response);
                    case 401:
                        return $this->response401($extra, $response);
                    case 403:
                        return $this->response403($extra, $response);
                    case 404:
                        return $this->response404($extra, $response);
                    case 419:
                        return $this->response400($extra, $response);
                    case 422:
                        return $this->response422($extra, $response);
                    case 500:
                        return $this->response500($extra, $response);
                }
            } else {
                return abort($status, $response);
            }
        }

        return $this->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
