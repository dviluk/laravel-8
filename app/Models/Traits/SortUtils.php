<?php

namespace App\Models\Traits;

trait SortUtils
{
    public function getSortableColumns()
    {
        return array_flip(array_merge($this->fillable, ['created_at', 'updated_at', 'deleted_at']));
    }
}
