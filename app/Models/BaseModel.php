<?php

namespace App\Models;

use App\Models\Traits\SortUtils;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use SoftDeletes, SortUtils;
}
