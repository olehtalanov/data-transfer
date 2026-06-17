<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Attributes;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Cast
{
    public function __construct(
        public string|UnitEnum $type,
        public mixed $default = null
    ) {}
}
