<?php

namespace App\Utils;

class ArrayUtils
{
    /**
     * Los valores del arreglo se convierten en keys y se le asigna el valor retornado
     * por el closure.
     * @param array $array 
     * @param \Closure $attach 
     * @return array 
     */
    public function arrayValuesAsKeysWithData(array $array, \Closure $attach)
    {
        $pivot = [];

        foreach ($array as $item) {
            $pivot[$item] = $attach($item);
        }

        return $pivot;
    }

    /**
     * Retorna un arreglo sin los keys especificados.
     * 
     * @param array $array 
     * @param array $keysToOmit 
     * @return array 
     */
    public function omitKeys(array $array, array $keysToOmit)
    {
        return array_diff_key($array, array_flip($keysToOmit));
    }

    /**
     * Retorna un arreglo con solo los elementos de los keys especificado.
     * @param array $array 
     * @param array $keysToPreserve 
     * @return array 
     */
    public function preserveKeys(array $array, array $keysToPreserve)
    {
        return array_intersect_key($array, array_flip($keysToPreserve));
    }

    /**
     * Retorna un arreglo sin los valores especificados.
     * 
     * @param array $array 
     * @param array $valuesToOmit 
     * @return array 
     */
    public function omitValues(array $array, array $valuesToOmit)
    {
        return array_diff($array, $valuesToOmit);
    }

    /**
     * Extrae el id pivote y lo usa como key de los elementos.
     * 
     * @param array $array 
     * @param string $pivotKey 
     * @param null|array $attachExtraData 
     * @return array 
     */
    public function formatPivotData(array $array, string $pivotKey = 'id', ?array $attachExtraData = null)
    {
        $data = [];

        foreach ($array as $item) {
            $key = $item[$pivotKey];

            unset($item[$pivotKey]);

            if ($attachExtraData) {
                $item = array_merge($item, $attachExtraData);
            }

            $data[$key] = $item;
        }

        return $data;
    }
}
