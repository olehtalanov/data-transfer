<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\Attributes\Hidden;
use Talanov\DataTransfer\DataTransferObject;

final class HiddenDto extends DataTransferObject
{
    public string $name;

    #[Hidden]
    public string $secret;
}
