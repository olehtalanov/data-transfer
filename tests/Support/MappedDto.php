<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\Attributes\MapOutputName;
use Talanov\DataTransfer\DataTransferObject;

final class MappedDto extends DataTransferObject
{
    #[MapOutputName('full_name')]
    public string $name;

    #[MapOutputName('user_age')]
    public int $age;
}
