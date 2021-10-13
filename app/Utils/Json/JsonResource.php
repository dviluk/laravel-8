<?php

namespace App\Utils\Json;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;

class JsonResource implements Responsable
{
    /**
     * Contiene el recurso principal.
     * 
     * @var mixed
     */
    protected $resource;

    /**
     * Indica el nombre de la propiedad que se usara como wrapper.
     * 
     * @var string
     */
    protected $dataWrapper = 'data';

    /**
     * Contiene el recurso formateado.
     * 
     * @var array
     */
    protected array $data = [];

    /**
     * Contiene datos extra al recurso.
     * 
     * Ej. Datos de pagination.
     * 
     * @var array
     */
    protected array $extraData = [];

    private array $statuses = [
        200 => 'Success',
        201 => 'Created',
    ];

    protected array $overrideStatuses = [];

    protected array $formatterOptions = [];

    /**
     * JsonResource
     * 
     * @param mixed $resource Recurso a formatear.
     * @param array $extraData Información extra para anexar.
     * @return void 
     * @throws \Illuminate\Database\Eloquent\InvalidCastException 
     */
    public function __construct($resource, array $extraData = [], array $formatterOptions = [])
    {
        $this->resource = $resource;
        $this->formatterOptions = $formatterOptions;
        $this->data = $this->formatResource($this->resource);
        $this->statuses = $this->statuses + $this->overrideStatuses;
        $this->extraData = array_merge($this->extraData, $extraData);
    }

    /**
     * Le da formato al recurso principal.
     * 
     * @param mixed $resource 
     * @return array 
     */
    private function formatResource($resource): array
    {
        if ($resource instanceof Collection || is_array($resource)) {
            $data = [];

            foreach ($resource as $item) {
                $data[] = $this->formatter($item, $this->formatterOptions);
            }

            return $data;
        } else if ($resource instanceof LengthAwarePaginator) {
            $data = $this->formatResource($resource->items());

            $total = $resource->total();

            $this->extraData = [
                'links' => [
                    'next' => $resource->nextPageUrl(),
                    'prev' => $resource->previousPageUrl(),
                    'last' => $resource->url($resource->lastPage()),
                    'first' => $resource->url(1),
                ],
                'total' => $total,
                'meta' => [
                    'current_page' => $resource->currentPage(),
                    'from' => $resource->firstItem(),
                    'last_page' => (int) $resource->lastPage(),
                    'path' => $resource->path(),
                    'per_page' => $resource->perPage(),
                    'total' => $total,
                    'to' => $resource->lastItem(),
                ],
            ];

            return $data;
        } else {
            return $this->formatter($resource, $this->formatterOptions);
        }
    }

    /**
     * Envuelve los datos formateados.
     * 
     * @return array 
     */
    private function dataWrapped()
    {
        $key = $this->dataWrapper;

        if (is_string($key)) {
            return [
                $key => $this->data
            ];
        }

        return $this->data;
    }

    /**
     * Genera una respuesta en base a los parámetros especificados.
     *
     * @param int $statusCode código de la respuesta
     * @param array $extra Información extra que se anexara
     * @param array $options Opciones adicionales
     *
     * - (array) `responseHeaders`: Headers de la respuesta
     * - (int) `responseOptions`: Configuraciones para `json_encode()`
     * 
     * @return void
     */
    private function prepareResponse(int $statusCode = 200, array $extra = [], array $options = [])
    {
        $data = [];

        if ($statusCode === 200) {
            $data['success'] = true;
        }

        $data = array_merge($data, $this->dataWrapped(), $this->extraData);

        if (count($extra) > 0) $data = array_merge($data, $extra);

        $responseHeaders = $options['responseHeaders'] ?? [];
        $responseOptions = $options['responseOptions'] ?? 0;

        return response()->json($data, $statusCode, $responseHeaders, $responseOptions);
    }

    /**
     * Da formato al recurso.
     * 
     * @param mixed $resource 
     * @return array 
     * @throws \Illuminate\Database\Eloquent\InvalidCastException 
     */
    protected function formatter($resource, array $formatterOptions): array
    {
        if ($resource instanceof \Eloquent || $resource instanceof \Illuminate\Database\Eloquent\Model) {
            return $resource->toArray();
        } else if (is_array($resource)) {
            return $resource;
        }

        throw new Exception('$this->resource not valid.');
    }

    /**
     * Retorna el recurso formateado en un arreglo.
     * 
     * @param bool $wrappedData 
     * @return array 
     */
    public function toArray($wrappedData = false): array
    {
        $data = $wrappedData ? $this->dataWrapped() : $this->data;

        $data = array_merge($data, $this->extraData);

        return $data;
    }

    /**
     * Retorna el recurso en una respuesta Json.
     * 
     * @param int $statusCode 
     * @param array $extra 
     * @param array $options 
     * @return void 
     * @throws \Illuminate\Contracts\Container\BindingResolutionException 
     */
    public function toJsonResponse(int $statusCode = 200, array $extra = [], array $options = [])
    {
        return $this->prepareResponse($statusCode, $extra, $options);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return $this->toJsonResponse();
    }
}
