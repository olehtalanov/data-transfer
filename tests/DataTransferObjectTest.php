<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Tests;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Talanov\DataTransfer\Casts\LowercaseCast;
use Talanov\DataTransfer\Casts\UppercaseCast;
use Talanov\DataTransfer\Tests\Support\CastedDto;
use Talanov\DataTransfer\Tests\Support\HiddenDto;
use Talanov\DataTransfer\Tests\Support\MappedDto;
use Talanov\DataTransfer\Tests\Support\SimpleDto;
use Talanov\DataTransfer\Tests\Support\TestStatus;

final class DataTransferObjectTest extends TestCase
{
    public function test_it_constructs_from_array(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);

        $this->assertSame('John', $dto->name);
        $this->assertSame(30, $dto->age);
    }

    public function test_it_sets_default_null_values(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertNull($dto->email);
    }

    public function test_it_casts_string_to_int(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => -5, 'views' => '42']);
        $this->assertSame(42, $dto->views);
    }

    public function test_it_casts_string_to_float(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => -5, 'balance' => '12.5']);
        $this->assertSame(12.5, $dto->balance);
    }

    public function test_it_casts_string_to_bool(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'active' => 'true']);
        $this->assertTrue($dto->active);
    }

    public function test_it_casts_abs_int(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => -10]);
        $this->assertSame(10, $dto->absolute);
    }

    public function test_it_casts_to_datetime(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'createdAt' => '2024-01-15 12:00:00']);
        $this->assertInstanceOf(CarbonImmutable::class, $dto->createdAt);
        $this->assertSame('2024-01-15 12:00:00', $dto->createdAt->format('Y-m-d H:i:s'));
    }

    public function test_it_casts_to_backed_enum_from_value(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'status' => 'active']);
        $this->assertInstanceOf(TestStatus::class, $dto->status);
        $this->assertSame(TestStatus::Active, $dto->status);
    }

    public function test_it_casts_to_backed_enum_from_instance(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'status' => TestStatus::Active]);
        $this->assertSame(TestStatus::Active, $dto->status);
    }

    public function test_it_applies_uppercase_cast(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'eur', 'absolute' => 1]);
        $this->assertSame('EUR', $dto->currency);
    }

    public function test_it_uses_custom_cast_class_directly(): void
    {
        $cast = new UppercaseCast();
        $this->assertSame('HELLO', $cast->handle('hello'));

        $lower = new LowercaseCast();
        $this->assertSame('hello', $lower->handle('HELLO'));
    }

    public function test_it_uses_default_value_when_null(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1]);
        $this->assertSame(0, $dto->views);
        $this->assertSame(0.0, $dto->balance);
        $this->assertFalse($dto->active);
    }

    public function test_it_converts_to_array(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['name' => 'John', 'age' => 30, 'email' => null], $dto->toArray());
    }

    public function test_it_hides_hidden_properties(): void
    {
        $dto = new HiddenDto(['name' => 'John', 'secret' => 'hidden_value']);
        $this->assertSame(['name' => 'John'], $dto->toArray());
    }

    public function test_it_maps_output_names(): void
    {
        $dto = new MappedDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['full_name' => 'John', 'user_age' => 30], $dto->toArray());
    }

    public function test_it_filters_with_only(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['name' => 'John'], $dto->only('name'));
    }

    public function test_it_filters_with_only_array(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['name' => 'John', 'age' => 30], $dto->only(['name', 'age']));
    }

    public function test_it_filters_with_except(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['name' => 'John', 'email' => null], $dto->except('age'));
    }

    public function test_it_filters_with_except_array(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertSame(['name' => 'John'], $dto->except(['age', 'email']));
    }

    public function test_it_merges_extra_data(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $result = $dto->merge(['extra' => 'value']);
        $this->assertSame($dto, $result);
        $this->assertSame('value', $dto->extra);
        $this->assertSame(['name' => 'John', 'age' => 30, 'email' => null, 'extra' => 'value'], $dto->toArray());
    }

    public function test_it_conditionally_merges(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $dto->mergeWhen(true, ['extra' => 'value']);
        $this->assertSame('value', $dto->extra);

        $dto2 = new SimpleDto(['name' => 'John', 'age' => 30]);
        $dto2->mergeWhen(false, ['extra' => 'value']);
        $this->assertNull($dto2->extra);
    }

    public function test_it_converts_to_json(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertJson($dto->toJson());
        $this->assertStringContainsString('"name":"John"', $dto->toJson());
    }

    public function test_it_json_serializes(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $serialized = json_encode($dto);
        $this->assertNotFalse($serialized);
        $this->assertStringContainsString('"name":"John"', $serialized);
    }

    public function test_it_throws_on_empty_data_for_non_nullable_properties(): void
    {
        $this->expectException(\TypeError::class);
        new SimpleDto([]);
    }

    public function test_it_returns_null_for_non_existent_extra(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30]);
        $this->assertNull($dto->non_existent);
    }

    public function test_it_ignores_unknown_data_keys(): void
    {
        $dto = new SimpleDto(['name' => 'John', 'age' => 30, 'unknown_key' => 'value']);
        $this->assertSame('John', $dto->name);
        $this->assertSame(30, $dto->age);
    }

    public function test_it_casts_to_datetime_with_carbon_instance(): void
    {
        $carbon = new CarbonImmutable('2024-06-15');
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'createdAt' => $carbon]);
        $this->assertSame($carbon, $dto->createdAt);
    }

    public function test_it_returns_null_for_invalid_datetime_string(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'createdAt' => 'not_a_date']);
        $this->assertNull($dto->createdAt);
    }

    public function test_it_returns_null_for_empty_datetime_string(): void
    {
        $dto = new CastedDto(['name' => 'test', 'currency' => 'usd', 'absolute' => 1, 'createdAt' => '']);
        $this->assertNull($dto->createdAt);
    }
}
