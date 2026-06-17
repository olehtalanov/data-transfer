<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests\Support;

use Talanov\DataTransfer\Attributes\Cast;
use Talanov\DataTransfer\Casts\UppercaseCast;
use Talanov\DataTransfer\DataTransferObject;

final class CastedDto extends DataTransferObject
{
    public string $name;

    #[Cast(UppercaseCast::class)]
    public string $currency;

    #[Cast('int', 0)]
    public ?int $views = null;

    #[Cast('float', 0.0)]
    public ?float $balance = null;

    #[Cast('bool', false)]
    public ?bool $active = null;

    #[Cast('abs_int')]
    public int $absolute;

    #[Cast('datetime')]
    public ?\Carbon\CarbonInterface $createdAt = null;

    #[Cast(TestStatus::class)]
    public ?TestStatus $status = null;
}
