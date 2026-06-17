<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Concerns;

interface CastInterface
{
    public function handle($value);
}
