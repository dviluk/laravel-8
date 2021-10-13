<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * Acciones básicas de relaciones many-to-many.
 * 
 * @method static self ATTACH()
 * @method static self DETACH()
 * @method static self DETACH_ALL()
 * @method static self SYNC()
 */
final class ManyToManyAction extends Enum
{
    private const ATTACH = 'ATTACH';
    private const DETACH = 'DETACH';
    private const DETACH_ALL = 'DETACH_ALL';
    private const SYNC = 'SYNC';
}
