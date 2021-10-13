<?php

namespace App\Utils;

use Illuminate\Support\Str;

class StringUtils
{
    public function toSlug(?string $name)
    {
        if (is_null($name)) {
            return null;
        }

        return Str::slug($name);
    }
}
