<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * Acciones básicas de relaciones many-to-many.
 * 
 * @method static self DIR()
 */
final class Directories extends Enum
{
    // IMPORTANT: Todos los directorios inician sin diagonal y terminan con diagonal
    private const DIR = 'dir/';
}
