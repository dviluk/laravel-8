<?php

namespace App\Repositories\Traits;

use Arrays;
use DB;
use Language;
use Throwable;

trait LocalizedMethodsTrait
{
    /**
     * Consulta todos los registros con traducciones.
     *
     * @param array $options Las mismas opciones que en `Repository::prepareQuery($options)`
     * @return \Illuminate\Support\Collection|\Eloquent[]
     * @throws \Error
     */
    public function allLocalized(array $options = [])
    {
        return $this->all(array_merge($options, [
            'byLang' => true,
            'columns' => $this->columnsTranslated,
        ]));
    }

    /**
     * Consulta todos los registros con traducciones.
     *
     * @param int $perPage numero de paginas
     * @param array $options Las mismas opciones que en `Repository::prepareQuery($options)`
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Error
     */
    public function paginatedLocalized($perPage = 15, array $options = [])
    {
        return $this->paginated($perPage, array_merge($options, [
            'byLang' => true,
            'columns' => $this->columnsTranslated,
        ]));
    }

    /**
     * Busca un registro por ID con traducciones.
     *
     * @param int $id
     * @param array $options Las mismas opciones que en `Repository::prepareQuery($options)`
     * @return null|\Eloquent
     * @throws \Error
     */
    public function findLocalized($id, array $options = [])
    {
        return $this->find($id, array_merge($options, [
            'byLang' => true,
            'columns' => $this->columnsTranslated
        ]));
    }

    /**
     * Busca un registro por ID con traducciones, si no se encuentra se genera un error.
     *
     * @param int $id
     * @param array $options
     * @return \Eloquent
     */
    public function findOrFailLocalized($id, array $options = [])
    {
        return $this->findOrFail($id, array_merge($options, [
            'byLang' => true,
            'columns' => $this->columnsTranslated
        ]));
    }

    /**
     * Crea un nuevo registro localizado
     * @param array $data 
     * @param array $options 
     * @return \Eloquent
     * @throws \Exception 
     * @throws \Throwable 
     */
    public function baseCreateLocalized(array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $item = $this->_createLocalized($data, $options);

            DB::commit();

            return $item;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza un registro localizado.
     * @param mixed $id 
     * @param array $data 
     * @param array $options 
     * @return \Eloquent 
     * @throws \Exception 
     * @throws \Throwable 
     */
    public function baseUpdateLocalized($id, array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $item = $this->_updateLocalized($id, $data);

            DB::commit();

            return $item;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crea un registro y sus traducciones.
     * 
     * @param array $data 
     * @param array $options `localizedKeysToPrimaryTable` & `localizedKeysToTranslationsTable`
     * @return mixed 
     * @throws \Exception 
     * @throws \Throwable 
     */
    private function _createLocalized(array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $data = $this->prepareData($data, $options, 'create');

            $options = array_merge($options, ['byLang' => true]);
            $extraData = $options['extraData'] ?? [];

            // Se mapea los keys de las traducciones que corresponden a la tabla de la db
            $mapLocalizedKeysToPrimaryTable = $options['localizedKeysToPrimaryTable'] ?? $this->getLocalizedKeysToPrimaryTableMap();
            // Se mapea los keys de las traducciones que corresponden a la tabla de traducciones en la db
            $mapLocalizedKeysToTranslationsTable = $options['localizedKeysToTranslationsTable'] ?? $this->getLocalizedKeysToTranslationsTableMap();

            // Se extraen las traducciones del registro
            $extractedTranslations = Language::extractTranslations($data, $mapLocalizedKeysToTranslationsTable, $extraData);
            $translations = $extractedTranslations['translations'];
            $defaultTranslations = $extractedTranslations['default_translation'];
            $currentTranslations = $extractedTranslations['current_translation'];
            $data = $extractedTranslations['data'];

            // Se agrega la traducción por default
            foreach ($mapLocalizedKeysToPrimaryTable as $localizedKey => $tableKey) {
                if (isset($defaultTranslations[$localizedKey])) {
                    $data[$tableKey] = $defaultTranslations[$localizedKey];
                }
            }

            $pivotData = Arrays::formatPivotData($translations, 'lang_id');

            $item = $this->create($data);

            $item->translations()->sync($pivotData);

            DB::commit();

            // Se sobrescribe con la traducción para el idioma actual de manera temporal
            foreach ($mapLocalizedKeysToPrimaryTable as $localizedKey => $tableKey) {
                if (isset($currentTranslations[$localizedKey])) {
                    $item->{$tableKey} = $currentTranslations[$localizedKey];
                }
            }

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Actualiza un registro y sus traducciones.
     * 
     * @param mixed $id 
     * @param array $data 
     * @param array $options `localizedKeysToPrimaryTable` & `localizedKeysToTranslationsTable`
     * @return mixed 
     * @throws \Exception 
     * @throws \Throwable 
     */
    private function _updateLocalized($id, array $data, array $options = [])
    {
        DB::beginTransaction();
        try {
            $data = $this->prepareData($data, $options, 'update');

            $options = array_merge($options, ['byLang' => true]);
            $extraData = $options['extraData'] ?? [];

            // Se mapea los keys de las traducciones que corresponden a la tabla de la db
            $mapLocalizedKeysToPrimaryTable = $options['localizedKeysToPrimaryTable'] ?? $this->getLocalizedKeysToPrimaryTableMap();
            // Se mapea los keys de las traducciones que corresponden a la tabla de traducciones en la db
            $mapLocalizedKeysToTranslationsTable = $options['localizedKeysToTranslationsTable'] ?? $this->getLocalizedKeysToTranslationsTableMap();

            $item = $this->findOrFail($id, $options);

            // Se extraen las traducciones del registro
            $extractedTranslations = Language::extractTranslations($data, $mapLocalizedKeysToTranslationsTable, $extraData);
            $translations = $extractedTranslations['translations'];
            $defaultTranslations = $extractedTranslations['default_translation'];
            $currentTranslations = $extractedTranslations['current_translation'];
            $data = $extractedTranslations['data'];

            // Se agrega la traducción por default
            foreach ($mapLocalizedKeysToPrimaryTable as $localizedKey => $tableKey) {
                if (isset($defaultTranslations[$localizedKey])) {
                    $data[$tableKey] = $defaultTranslations[$localizedKey];
                }
            }

            $pivotData = Arrays::formatPivotData($translations, 'lang_id');

            $this->canUpdate($item, $data);

            $item = $this->update($id, $data);

            $item->translations()->sync($pivotData);

            DB::commit();

            // Se sobrescribe con la traducción para el idioma actual
            foreach ($mapLocalizedKeysToPrimaryTable as $localizedKey => $tableKey) {
                if (isset($currentTranslations[$localizedKey])) {
                    $item->{$tableKey} = $currentTranslations[$localizedKey];
                }
            }

            return $item;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function getLocalizedKeysToPrimaryTableMap()
    {
        return $this->localizedKeysToPrimaryTable ?? ['name' => 'name'];
    }

    private function getLocalizedKeysToTranslationsTableMap()
    {
        return $this->mapLocalizedKeysToTranslationsTable ?? ['name' => 'name_translated'];
    }
}
