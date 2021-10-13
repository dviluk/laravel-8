<?php

namespace App\Http\Controllers;

use DB;
use API;
use App\Utils\API\Error500;
use Illuminate\Http\Request;

class CRUDController extends Controller
{
    /**
     * Instancia del repositorio.
     * 
     * @var \App\Repositories\Repository
     */
    protected $repo;

    /**
     * @var \App\Utils\Json\JsonResource
     */
    protected $resource;

    /**
     * Indica si se utilizaran los métodos localizados del repositorio.
     * 
     * @var boolean
     */
    protected $localized = false;

    public function __construct()
    {
        if (!is_string($this->repo)) {
            throw new Error500([], '$this->repo not valid');
        }

        if (!is_string($this->resource)) {
            throw new Error500([], '$this->resource not valid');
        }

        $this->repo = new $this->repo;
    }

    /**
     * Get data from update request.
     * 
     * @param \Illuminate\Http\Request $request 
     * @param string $id 
     * @return array 
     */
    protected function updateValidator(Request $request, string $id): array
    {
        return $this->repo->inputRules($request, 'update', $id);
    }

    /**
     * Get data from update request.
     * 
     * @param \Illuminate\Http\Request $request 
     * @param string $id 
     * @return array 
     */
    protected function getUpdateData(Request $request, string $id): array
    {
        return $request->all();
    }

    /**
     * Validate store request input.
     * 
     * @param \Illuminate\Http\Request $request 
     * @return array 
     */
    protected function storeValidator(Request $request): array
    {
        return $this->repo->inputRules($request, 'create');
    }

    /**
     * Get data from store request.
     * 
     * @param \Illuminate\Http\Request $request 
     * @return array 
     */
    protected function getStoreData(Request $request): array
    {
        return $request->all();
    }

    /**
     * Indica las relaciones que se cargaran según el método indicado.
     * 
     * @param string $method 
     * @return array 
     */
    protected function loadRelations(string $method)
    {
        return [];
    }

    /**
     * Retorna las opciones que se aplicaran en el método indicado.
     * 
     * @param mixed $method 
     * @return array 
     */
    protected function options(string $method, Request $request)
    {
        return [];
    }

    /**
     * Permite ejecutar una acción antes de ejecutar el `$repo->create()` o `$repo->update()`.
     * 
     * @param string $method 
     * @param array $data 
     * @param int|null $id Se para cuando $method = 'update'
     * @param \Eloquent|null $item Se para cuando $method = 'update'
     * @return void 
     */
    protected function preAction(string $method, array $data, $id = null, $item = null): array
    {
        return $data;
    }

    /**
     * Permite ejecutar una acción después de ejecutar el `$repo->create()` o `$repo->update()`.
     * 
     * @param string $method 
     * @param array $data 
     * @return void 
     */
    protected function postAction(string $method, $item)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $method = $this->localized  ? 'paginatedLocalized' : 'paginated';

        $queryOptions = $this->_queryOptions('index', [
            'with' => $this->loadRelations('index'),
            'params' => $request->all(),
            'sort' => $request->sort,
            'onlyTrashed' => $request->boolean('onlyTrashed'),
        ], $request);

        $resourceOptions = $queryOptions['resourceOptions'] ?? [];

        // Indica si el resultado se utiliza par un select
        $forSelect = $request->boolean('select');

        if ($forSelect) {
            $resourceOptions['select'] = true;
            $items = $this->repo->all($queryOptions);
        } else {
            $items = $this->repo->{$method}(15, $queryOptions);
        }


        return new $this->resource($items, [], $resourceOptions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $method = $this->localized  ? 'createLocalized' : 'create';

        $request->validate($this->storeValidator($request));

        $data = $this->getStoreData($request);

        $queryOptions = $this->_queryOptions('store', [], $request);

        $resourceOptions = $queryOptions['resourceOptions'] ?? [];

        DB::beginTransaction();
        try {
            $data = $this->preAction('store', $data);

            $item = $this->repo->{$method}($data);

            $this->postAction('store', $item);

            $item->load($this->loadRelations('show'));

            DB::commit();

            return new $this->resource($item, [],  $resourceOptions);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $method = $this->localized  ? 'findOrFailLocalized' : 'findOrFail';

        $queryOptions = $this->_queryOptions('show', [
            'with' => $this->loadRelations('show'),
            'onlyTrashed' => $request->boolean('onlyTrashed'),
        ], $request);

        $resourceOptions = $queryOptions['resourceOptions'] ?? [];

        $item = $this->repo->{$method}($id, $queryOptions);

        return new $this->resource($item, [], $resourceOptions);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $queryOptions = $this->_queryOptions('edit', [
            'with' => $this->loadRelations('edit'),
            'onlyTrashed' => $request->boolean('onlyTrashed'),
        ], $request);

        $resourceOptions = $queryOptions['resourceOptions'] ?? [];
        $resourceOptions['editing'] = true;

        $item = $this->repo->findOrFail($id, $queryOptions);

        return new $this->resource($item, [], $resourceOptions);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $method = $this->localized  ? 'updateLocalized' : 'update';

        $request->validate($this->updateValidator($request, $id));

        $data = $this->getUpdateData($request, $id);

        $queryOptions = $this->_queryOptions('update', [
            'onlyTrashed' => $request->boolean('onlyTrashed'),
        ], $request);

        $resourceOptions = $queryOptions['resourceOptions'] ?? [];

        DB::beginTransaction();
        try {
            $item = $this->repo->findOrFail($id, $queryOptions);

            $data = $this->preAction('update', $data, $id, $item);

            $item = $this->repo->{$method}($item, $data, $queryOptions);

            $this->postAction('update', $item);

            $item->load($this->loadRelations('show'));

            DB::commit();

            return new $this->resource($item, [], $resourceOptions);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $queryOptions = $this->_queryOptions('destroy', [
            'onlyTrashed' => $request->boolean('onlyTrashed'),
        ], $request);

        $this->repo->delete($id, $queryOptions);

        return API::response200();
    }

    /**
     * Restaura un registro softDeleted.
     * 
     * @param \Illuminate\Http\Request $request 
     * @param mixed $id 
     * @return \Illuminate\Http\JsonResponse 
     */
    public function restore(Request $request, $id)
    {
        $queryOptions = $this->_queryOptions('restore', [], $request);

        $this->repo->restore($id, $queryOptions);

        return API::response200();
    }

    /**
     * Retorna las opciones que se pasaran a la consulta.
     * 
     * @param mixed $method 
     * @param array $attach 
     * @return array 
     */
    private function _queryOptions($method, $attach = [], Request $request = null)
    {
        return array_merge_recursive($this->options($method, $request), $attach);
    }
}
