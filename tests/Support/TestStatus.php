<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

enum TestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
