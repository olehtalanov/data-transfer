<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Casts;

use Talanov\DataTransfer\Concerns\CastInterface;

final class LowercaseCast implements CastInterface
{
    public function handle($value): string
    {
        return strtolower((string) $value);
    }
}
