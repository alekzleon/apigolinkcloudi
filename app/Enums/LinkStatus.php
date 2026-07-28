<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
