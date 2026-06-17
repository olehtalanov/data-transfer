<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\DataTransferObject;

final class SimpleDto extends DataTransferObject
{
    public string $name;
    public int $age;
    public ?string $email = null;
}
