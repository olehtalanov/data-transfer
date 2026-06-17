# Simple library of Data Transfer Object for PHP

No overheads, no hidden magic.

## Installation setup

```composer require talanov/data-transfer```

## Usage

```php
use Talanov\DataTransferObject\Attributes\Cast;
use Talanov\DataTransferObject\Casts\UppercaseCast;
use Talanov\DataTransferObject\DataTransferObject;

final class AccountData extends DataTransferObject
{
    public string $name;
    
    #[Cast(UppercaseCast::class)]
    public string $currency;

    public ?string $iban = null;

    #[Cast('float', 0.0)]
    public ?float $balance = null;

    public ?string $description = null;
}
```

```php
$account = new AccountData([
    'name' => 'My account',
    'currency' => 'EUR',
    'iban' => 'DE89370400440532013000',
    'balance' => '123.45',
    'description' => 'My first account',
]);
```