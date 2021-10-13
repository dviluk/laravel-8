<?php

namespace App\Repositories\Traits;

use App\Repositories\Repository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait RepositoryUtils
{
    /**
     * Valida si el $id es una instancia de un modelo.
     * 
     * @param mixed $id 
     * @return bool 
     */
    protected function idIsModel($id)
    {
        return $id instanceof Model;
    }

    /**
     * Agrega el ordenamiento segun los handler.
     * 
     * @param array $options 
     * @param array $handlers 
     * @return void 
     */
    protected function handleSort(array $options, array $handlers)
    {
        $sortOptions = $options['sort'] ?? null;

        if (!$sortOptions) return;

        foreach ($sortOptions as $sort) {
            $column = $sort['column'];
            $direction = Repository::DIRECTIONS[$sort['direction']] ?? null;

            $handler = $handlers[$column] ?? null;

            if ($handler) {
                $handler($column, $direction);
            }
        }
    }

    /**
     * Se encarga de input de tipo search.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder 
     * @param array $params 
     * @param string $input 
     * @param array $columns 
     * @return void 
     * @throws \InvalidArgumentException 
     */
    protected function handleSearchInput(Builder $builder,  &$input, array $columns = [])
    {
        if ($input === null) {
            return;
        }

        $value = $input;

        $columnsLen = count($columns);

        if ($columnsLen === 0) {
            $builder->where($input, 'like', "%{$value}%");
        } else if ($columnsLen === 1) {
            $builder->where($columns[0], 'like', "%{$value}%");
        } else if ($columnsLen > 1) {
            $builder->where(function ($q) use ($columns, $value) {
                foreach ($columns as $index => $col) {
                    if ($index === 0) {
                        $q->where($col, 'like', "%{$value}%");
                    } else {
                        $q->orWhere($col, 'like', "%{$value}%");
                    }
                }
            });
        }
    }

    /**
     * Se encarga del input de tipo DateRange/Date/Datetime.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder 
     * @param string $input 
     * @param boolean $useTimezone
     * @return void 
     * @throws \InvalidArgumentException 
     */
    protected function handleDateInput(Builder $builder,  &$input, bool $useTimezone = false)
    {
        if ($input === null) {
            return;
        }

        $value = $input;

        if ($value === null) {
            $builder->whereNull($input);
        }

        $dateFormat = 'Y-m-d';
        $isDate = Carbon::hasFormat($value, $dateFormat);

        if ($isDate) {
            $builder->whereDate($input, $value);
            return;
        }

        $dateRangeFormat = 'Y-m-d - Y-m-d';
        $isDateRange = Carbon::hasFormat($value, $dateRangeFormat);

        if ($isDateRange) {
            $arr = explode(' - ', $value);

            $startDate = $arr[0];
            $endDate = $arr[1];

            if ($startDate === $endDate) {
                $builder->whereDate($input, $startDate);
                return;
            }

            $builder->whereBetween($input, $arr);
            return;
        }

        $datetimeFormat = 'Y-m-d H:i:s';
        $isDatetime = Carbon::hasFormat($value, $datetimeFormat);

        if ($isDatetime) {
            // TODO: Crear utilidad para convertir el timezone de los datetime que mande el usuario
            if ($useTimezone && $timezone = request()->headers->get('timezone')) {
                $value = Carbon::createFromFormat($datetimeFormat, $value, $timezone)->setTimezone('UTC')->format($datetimeFormat);
            }

            $builder->where($input, $value);
            return;
        }

        $datetimeRangeFormat = 'Y-m-d H:i:s - Y-m-d H:i:s';
        $isDatetimeRange = Carbon::hasFormat($value, $datetimeRangeFormat);

        if ($isDatetimeRange) {
            $arr = explode(' - ', $value);

            $startDate = $arr[0];
            $endDate = $arr[1];

            if ($startDate === $endDate) {
                $builder->where($input, $startDate);
                return;
            }

            // TODO: Crear utilidad para convertir el timezone de los datetime que mande el usuario
            if ($useTimezone && $timezone = request()->headers->get('timezone')) {
                $startDate = Carbon::createFromFormat($datetimeFormat, $startDate, $timezone)->setTimezone('UTC')->format($datetimeFormat);
                $endDate = Carbon::createFromFormat($datetimeFormat, $endDate, $timezone)->setTimezone('UTC')->format($datetimeFormat);
            }

            $builder->whereBetween($input, [$startDate, $endDate]);
            return;
        }
    }


    protected function handleWhereHas(Builder $builder, string $relation, &$input, string $column = 'id')
    {
        if ($input === null) {
            return;
        }

        $builder->whereHas($relation, function ($q) use ($input, $column) {
            $q->where($column, $input);
        });
    }
}
