<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\Attributes\MapInputName;
use Talanov\DataTransfer\DataTransferObject;

final class MappedInputDto extends DataTransferObject
{
    #[MapInputName('full_name')]
    public string $name;

    #[MapInputName('user_age')]
    public int $age;

    public ?string $email = null;
}
