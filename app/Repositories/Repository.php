<?php

namespace App\Repositories;

use App\Enums\ManyToManyAction;
use App\Utils\API\Error404;
use App\Utils\API\Error500;
use App\Models\Traits\SortUtils;
use Arrays;
use Closure;
use DB;
use Eloquent;
use Error;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Language;
use Throwable;

/**
 * Repositorio base para manipular el CRUD de un modelo Laravel
 */
class Repository
{
    const DIRECTIONS = [
        'ascend' => 'asc',
        'descend' => 'desc',
    ];

    /**
     * Si es verdadero todas las validaciones de los métodos can[Method] se omitirán.
     * 
     * @var bool
     */
    private $applyValidations = true;

    /**
     * Indica si el modelo usa SoftDeletes.
     * 
     * @var bool
     */
    private $useSoftDeletes = false;

    /**
     * Modelo principal de repositorio.
     *
     * En la clase hijo se debe declarar la clase del modelo
     * de la siguiente manera:
     *
     * ```php
     * protected $model = Model::class;
     * ```
     *
     * @var string
     */
    protected $model;

    /**
     * @var \Eloquent
     */
    protected $modelInstance;

    /**
     * Indica por que columna ordenar los resultados.
     * 
     * ['column', 'asc'|'desc', ?'localized']
     * 
     * (opcional) Si se indica `localized` se buscara la columna en la db según el idioma actual de 
     * la aplicación.
     * 
     * Ej. Se pasa la columna `product`, se ordenara por la columna `product_en` si la
     * aplicación esta en ingles.
     * 
     * @var array
     */
    protected $orderBy = [];

    protected $defaultColumns = ['*'];

    public function __construct()
    {
        $this->validateModel();

        $this->useSoftDeletes = $this->useTrait($this->model, SoftDeletes::class);

        // Se crea instancia del modelo
        $this->modelInstance = new $this->model;
    }

    /**
     * Prepara el query base.
     *
     * @param array $options
     *
     *  - array `$options['where']` Contiene las condiciones básicas que se aplicaran al query.
     *
     * Ej:
     * ```php
     *  $where = [
     *       [column, operator, value],
     *       [column, operator, value],
     *  ];
     * ```
     *
     *  - array `$options['with']` Contiene las relaciones que se pre-cargaran junto al modelo.
     *
     * Ej:
     * ```php
     * $with = [
     *     relation => closure
     * ];
     *
     * // O
     *
     * $with = relation;
     * ```
     *
     *  - \Closure `$options['builder']` El closure recibe el $query como parámetro, para crear queries mas avanzados.
     *
     * Ej:
     * ```php
     * $builder = closure(Builder $queryBuilder);
     * ```
     *
     * - string|int `$options['find']` Elemento a buscar.
     * - string `$options['columnId']` Nombre de la columna donde buscara el valor de `$options['find']`.
     *
     * @return Builder
     * @throws Error
     */
    private function initQuery($options = [])
    {
        $query = $this->modelInstance::query();

        $columnId = $this->modelInstance->getKeyName();

        if (isset($options['sort'])) {
            $sortOptions = $options['sort'];

            // Se obtienen las columnas que se pueden ordenar
            if ($this->useTrait($this->model, SortUtils::class)) {
                $validColumns = $this->modelInstance->getSortableColumns();
            } else {
                $validColumns = array_flip($this->modelInstance->getFillable());
            }

            foreach ($sortOptions as $sort) {
                $column = $sort['column'];
                $direction = Repository::DIRECTIONS[$sort['direction']] ?? null;

                if ($direction && array_key_exists($column, $validColumns)) {
                    $query->orderBy($column, $direction);
                }
            }
        } else if (is_array($this->orderBy) && count($this->orderBy) >= 2) {
            $localize = isset($this->orderBy[2]) && $this->orderBy[2] === 'localized';

            $column = $this->orderBy[0];

            if ($localize) {
                $column = Language::dbColumn($column);
            }

            $query->orderBy($column, $this->orderBy[1]);
        }

        if ($options instanceof Closure) {
            $options($query);
        } else if (is_array($options)) {
            if (array_key_exists('where', $options)) {
                $where = $options['where'];
                if (is_array($where)) {
                    $query->where($where);
                } else {
                    throw new Error("`\$options['where']` no es valido");
                }
            }

            if (array_key_exists('whereIn', $options)) {
                foreach ($options['whereIn'] as $whereIn) {
                    if (count($whereIn) === 2) {
                        $query->whereIn($whereIn[0], $whereIn[1]);
                    } else {
                        throw new Error500([], "`\$options['whereIn']` no es valido");
                    }
                }
            }

            if (array_key_exists('with', $options)) {
                $with = $options['with'];
                if (is_array($with) || is_string($with)) {
                    $query->with($with);
                } else {
                    throw new Error("`\$options['with']` no es valido");
                }
            }

            if (array_key_exists('has', $options)) {
                $has = $options['has'];
                if (is_array($has)) {
                    foreach ($has as $rel => $callback) {
                        $query->whereHas($rel, $callback);
                    }
                } else if (is_string($has)) {
                    $query->whereHas($has);
                } else {
                    throw new Error("`\$options['has']` no es valido");
                }
            }

            $builder = $options['builder'] ?? null;
            if ($builder instanceof \Closure) {
                $builder($query);
            }

            // en caso de que `id` no sea la llave primaria o
            // se desea utilizar otra columna para la búsqueda
            if (array_key_exists('columnId', $options)) {
                $columnId = $options['columnId'];
            }

            if (array_key_exists('find', $options)) {
                $query->where($columnId, $options['find']);
            }
        }

        if ($this->useSoftDeletes) {
            $trashedModel = $options['onlyTrashed'] ?? false;
            if ($trashedModel) {
                $query->onlyTrashed();
            }
        }

        return $query;
    }

    /**
     * Prepara los datos antes de insertar/actualizar un registro.
     * 
     * Se ejecuta antes de request validator.
     * 
     * @param array $data 
     * @param string $method
     * @param array $options 
     * @return array 
     */
    public function prepareData(array $data, string $method, array $options = [])
    {
        return $data;
    }

    /**
     * Permite encargarse de las opciones adicionales.
     *
     * @param Builder $builder
     * @param array $options
     * @return Builder
     */
    public function handleOptions(Builder $builder, array $options = [])
    {
        return $builder;
    }

    /**
     * Retornar el query configurado.
     *
     * @param array $options Las mismas opciones que en `Repository::initQuery($options)`
     * @return Builder
     * @throws Error
     */
    public function query(array $options = [])
    {
        $query = $this->initQuery($options);

        $this->handleOptions($query, $options);

        return $query;
    }

    /**
     * Consulta todos los registros.
     *
     * @param array $options Las mismas opciones que en `Repository::initQuery($options)`
     * @return Collection
     * @throws Error
     */
    public function all(array $options = [])
    {
        $query = $this->query($options);

        $columns = $options['columns'] ?? $this->defaultColumns;

        return $query->get($columns);
    }

    /**
     * Retorna los registros paginados.
     * 
     * @param int $perPage 
     * @param array $options 
     * @return LengthAwarePaginator 
     * @throws Error 
     * @throws InvalidArgumentException 
     */
    public function paginated($perPage = 15, $options = [])
    {
        $query = $this->query($options);

        if (config('app.debug') === false && !in_array($perPage, $this->paginationOptions())) {
            $perPage = 15;
        }

        $params = $options['params'] ?? null;

        $columns = $options['columns'] ?? $this->defaultColumns;
        $pageName = $options['pageName'] ?? 'current';

        if ($params !== null) {
            $page = $params['current'] ?? null;
            $perPage = $params['pageSize'] ?? $perPage;
        } else {
            $page = $options['current'] ?? null;
            $perPage = $options['pageSize'] ?? null;
        }

        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Retorna los números de pagina que se pueden aplicar.
     *
     * @return array
     */
    public function paginationOptions()
    {
        return [15, 25, 30, 50, 100];
    }

    /**
     * Busca un registro por ID.
     *
     * @param mixed $id
     * @param array $options Las mismas opciones que en `Repository::initQuery($options)`
     * @return null|Eloquent
     * @throws Error
     */
    public function find($id, array $options = [])
    {
        // El id puede ser una instancia del modelo principal,
        // eso quiere decir que se puede continuar usando el modelo
        // anteriormente consultado.
        if ($id instanceof $this->model) {
            return $id;
        }

        $query = $this->query(array_merge($options, ['find' => $id]));

        $columns = $options['columns'] ?? $this->defaultColumns;

        return $query->first($columns);
    }

    /**
     * Busca un registro por la columna name.
     * 
     * @param string $name 
     * @param array $options 
     * @return null|Eloquent
     * @throws \Error 
     */
    public function findByName($name, array $options = [])
    {
        return $this->find($name, array_merge($options, ['columnId' => 'name']));
    }

    /**
     * 
     * @param mixed $id 
     * @param array $options 
     * @return Eloquent 
     * @throws Error 
     * @throws Error404 
     */
    public function findOrFail($id, array $options = [])
    {
        $item = $this->find($id, $options);

        if (!$item) {
            throw new Error404();
        }

        return $item;
    }

    /**
     * Valida si se puede crear un registro.
     *
     * @param array $data
     * @return void
     */
    public function canCreate(array $data, array $options = [])
    {
        //
    }

    /**
     * Crea un nuevo registro.
     *
     * @param array $data Contiene los campos a insertar en la tabla del modelo.
     * @param array $options
     * @return Eloquent
     * @throws Exception
     * @throws Throwable
     */
    public function create(array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $data = Arrays::preserveKeys($data, $this->availableInputKeys($data, 'create', $options));

            $data = $this->prepareData($data, 'create', $options);

            if ($this->applyValidations) {
                $this->canCreate($data, $options);
            }

            $item = $this->modelInstance::create($data);

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Valida si se puede editar el registro.
     *
     * @param Eloquent $item
     * @return void
     */
    public function canUpdate($item, ?array $data = [], array $options = [])
    {
        //
    }

    /**
     * Actualiza un registro.
     *
     * @param mixed $id
     * @param array $data Contiene los campos a actualizar.
     * @param array $options
     * @return Eloquent
     * @throws Exception
     * @throws Throwable
     */
    public function update($id, array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $data = Arrays::preserveKeys($data, $this->availableInputKeys($data, 'update', $options));

            $data = $this->prepareData($data, 'update', $options);

            $item = $this->findOrFail($id, $options);

            if ($this->applyValidations) {
                $this->canUpdate($item, $data, $options);
            }

            $item->update($data);

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Valida si se puede eliminar el registro.
     *
     * @param Eloquent $item
     * @return void
     */
    public function canDelete($item, array $options = [])
    {
        //
    }

    /**
     * Elimina un registro.
     *
     * @param mixed $id
     * @param array $options
     * @return Eloquent|null
     * @throws Exception
     * @throws Throwable
     */
    public function delete($id, array $options = [])
    {
        DB::beginTransaction();
        try {
            $onDeletePermanently = $options['onDeletePermanently'] ?? function ($item) {
            };

            $item = $this->findOrFail($id, $options);

            if ($this->applyValidations) {
                $this->canDelete($item, $options);
            }

            $forceDelete = $options['onlyTrashed'] ?? false;

            // Se hace forceDelete() si se esta consultando un elemento `trashed` y usa SoftDeletes.
            if ($this->useSoftDeletes && $forceDelete === true) {
                $onDeletePermanently($item, $options);
                $item->forceDelete();
            } else {
                // Si usa SoftDeletes, se cambia el estado a trashed
                // Si no se usa, se elimina el registro de la DB
                $item->delete();

                if (!$this->useSoftDeletes) {
                    $onDeletePermanently($item, $options);
                }
            }

            DB::commit();

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            if (strpos($e->getMessage(), 'Integrity constraint violation: 1451') !== false) {
                throw new Error500([], 'This item cannot be deleted, has data associated.');
            }

            throw $e;
        }
    }

    public function canRestore($item, array $options = [])
    {
        //
    }

    /**
     * Restaura un registro si usa softDeletes.
     * 
     * @param mixed $id 
     * @param array $options 
     * @return void 
     * @throws \Error 
     * @throws \App\Utils\API\Error404 
     */
    public function restore($id, array $options = [])
    {
        if (!$this->useSoftDeletes) {
            throw new Error404();
        }

        $item = $this->findOrFail($id, array_merge($options, ['onlyTrashed' => true]));

        if ($this->applyValidations) {
            $this->canRestore($item, $options);
        }

        $item->restore();

        return $item;
    }

    /**
     * Se encarga de validar que el `$this->model`
     * sea un string valido.
     *
     * @return void
     * @throws Error
     */
    private function validateModel()
    {
        if (!is_string($this->model)) {
            throw new \Error('`$this->model` not valid:' . $this->model);
        }
    }

    /**
     * Acciones básicas para una relación many-to-many.
     * 
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation 
     * @param \App\Enums\ManyToManyAction $action 
     * @param array|null $data 
     * @param array $options
     * 
     * - (bool)   `isArrayOfIds`: default `true`
     * - (string)   `pivotKey`: default `id`
     * 
     * @return array 
     */
    protected function manyToManyActions(BelongsToMany $relation, ManyToManyAction $action, $data, array $options = [])
    {
        if (is_null($data)) {
            return [];
        } else if (!is_array($data)) {
            throw new Error500([], '$data is not valid');
        }

        $isAttachAction = $action->equals(ManyToManyAction::ATTACH());

        // Cuando es un AttachAction y no se tienen datos, se finaliza el proceso
        if ($isAttachAction && count($data) === 0) {
            return [];
        }

        $isSyncAction = $action->equals(ManyToManyAction::SYNC());

        $isInsertAction = $isAttachAction || $isSyncAction;

        $isArrayOfIds = $options['isArrayOfIds'] ?? true;
        $pivotKey = $options['pivotKey'] ?? 'id';

        // Detach solo recibe ids
        if ($action->equals(ManyToManyAction::DETACH())) {
            $isArrayOfIds = true;
        }

        // Cuando los datos no son un array de solo Ids, se le da formato de tal manera
        // cumpla con [$id => [$pivotData]]
        if (!$isArrayOfIds && $isInsertAction) {
            $data = Arrays::formatPivotData($data, $pivotKey);
        }

        // Cuando es un AttachAction, se omiten los items que ya existen
        if ($isAttachAction) {
            $currentIds = $relation->pluck('id')->toArray();

            if ($isArrayOfIds) {
                $data = Arrays::omitValues($data, $currentIds);
            } else {
                $data = Arrays::omitKeys($data, $currentIds);
            }
        }

        if ($isArrayOfIds) {
            $changes = $data;
        } else {
            $changes = array_keys($data);
        }

        switch ($action) {
            case ManyToManyAction::ATTACH():
                $relation->attach($data);
                break;
            case ManyToManyAction::DETACH():
                $relation->detach($data);
                break;
            case ManyToManyAction::DETACH_ALL():
                $relation->detach();
                break;
            case ManyToManyAction::SYNC():
                $changes = $relation->sync($data);
                break;
            default:
                throw new Error500([], $action . ': Action not found');
        }

        return $changes;
    }

    /**
     * Acción por default para modificar las relaciones de many-to-many.
     * 
     * @param int|\Eloquent $id 
     * @param \App\Enums\ManyToManyAction $action
     * @param array $data 
     * @param array $options 
     * @return mixed 
     * @throws \Error 
     * @throws \App\Utils\API\Error404 
     * @throws \InvalidArgumentException 
     * @throws \App\Utils\API\Error500 
     */
    public function defaultUpdateManyToManyRelation($id, ManyToManyAction $action, array $data = [], array $options = [])
    {
        $relationName = $options['relationName'];
        $relatedKey = $options['relatedKey'];

        $item = $this->findOrFail($id);

        $relation = $item->{$relationName}();

        $changes = $this->manyToManyActions($relation, $action, $data, $options);

        if (isset($options['returnAttachedItems'])) {
            return $relation->wherePivotIn($relatedKey, $changes)->get();
        }

        return $item;
    }

    /**
     * Contiene los keys de los posibles valores del atributo $data
     * en el método `create` y `update`.
     * 
     * @param array $data
     * @param string $method Indica el método donde se esta llamando.
     * @param array $options
     * @return array
     */
    public function availableInputKeys(array $data, string $method, array $options = [])
    {
        return [];
    }

    /**
     * Reglas que se aplicaran a los inputs.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $method Indica el método donde se esta llamando.
     * @param mixed $id
     * @param array $options
     * @return array
     */
    public function inputRules(Request $request, string $method, $id = null, array $options = [])
    {
        return [];
    }

    /**
     * Declara si las validaciones se ignoraran.
     * 
     * @param bool $ignore 
     * @return void 
     */
    public function setApplyValidations(bool $apply = true)
    {
        $this->applyValidations = $apply;
    }

    /**
     * Verifica si la clase indicada usa el trait especificado.
     * 
     * @param string $class 
     * @param string $hasTrait 
     * @return bool 
     */
    private function useTrait(string $class, string $hasTrait)
    {
        $parentClasses = class_parents($class);

        $classTraits = class_uses($class);
        $isFound = in_array($hasTrait, $classTraits);

        if ($isFound) {
            return true;
        }

        foreach ($parentClasses as $parentClass) {
            $parentTraits = class_uses($parentClass);
            $isFound = in_array($hasTrait, $parentTraits);

            if ($isFound) {
                return true;
            }
        }

        return false;
    }
}
