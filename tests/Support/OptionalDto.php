<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\Attributes\Optional;
use Talanov\DataTransfer\DataTransferObject;

final class OptionalDto extends DataTransferObject
{
    public string $name;

    #[Optional]
    public ?string $nickname = null;

    #[Optional]
    public ?int $rating = null;
}
