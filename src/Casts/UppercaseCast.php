<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Casts;

use Talanov\DataTransfer\Concerns\CastInterface;

final class UppercaseCast implements CastInterface
{
    public function handle($value): string
    {
        return strtoupper((string) $value);
    }
}
