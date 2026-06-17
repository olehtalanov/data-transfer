<?php

declare(strict_types=1);

namespace Talanov\DataTransfer;

use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use Talanov\DataTransfer\Attributes\Cast;
use Talanov\DataTransfer\Concerns\CastInterface;
use Talanov\DataTransfer\Traits\ResponseTrait;
use UnitEnum;

abstract class DataTransferObject
{
    use ResponseTrait;

    protected string $__property_name;

    public function __construct(...$data)
    {
        if (count($data) === 1 && is_array($data[0] ?? null)) {
            $data = $data[0];
        }

        $this->castAndFill($data);
    }

    public function only(array|string $fields): array
    {
        $original = $this->transform();
        $fields = is_string($fields) ? func_get_args() : $fields;

        $include = [];
        foreach ($fields as $field) {
            $include[$field] = null;
        }

        return array_intersect_key($original, $include);
    }

    public function except(array|string $fields): array
    {
        $original = $this->transform();
        $fields = is_string($fields) ? func_get_args() : $fields;

        $exclude = [];
        foreach ($fields as $field) {
            $exclude[$field] = null;
        }

        return array_diff_key($original, $exclude);
    }

    private function castAndFill(array $data): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $this->__property_name = $property->getName();
            $value = $data[$this->__property_name] ?? null;

            // Check for Cast attribute
            $castAttribute = $property->getAttributes(Cast::class)[0] ?? null;

            if ($castAttribute) {
                /** @var Cast $cast */
                $cast = $castAttribute->newInstance();
                $value = $this->castValue($value, $cast->type, $cast->default);
            } // Fallback to native PHP type
            elseif ($property->hasType()) {
                $value = $this->castToNativeType($value, $property->getType());
            }

            // Set value if possible
            if (! $property->isInitialized($this) || $value !== null || $property->getType()?->allowsNull()) {
                $property->setValue($this, $value);
            }
        }
    }

    private function castValue(mixed $value, string|UnitEnum $castType, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        if (is_string($castType) && enum_exists($castType)) {
            return $this->castToEnum($value, $castType);
        }

        return $this->caster($value, $castType);
    }

    private function castToNativeType(mixed $value, ReflectionType $type): mixed
    {
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (enum_exists($typeName)) {
                return $this->castToEnum($value, $typeName);
            }

            return $this->caster($value, $typeName);
        }

        return $value;
    }

    private function caster(mixed $value, mixed $castType): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            return match ($castType) {
                'int', 'integer' => (int) $value,
                'abs_int', 'abs_integer' => abs((int) $value),
                'float', 'double' => (float) $value,
                'abs_float', 'abs_double' => abs((float) $value),
                'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'string' => (string) $value,
                'array' => (array) $value,
                'datetime' => $this->castToDateTime($value),
                default => $this->resolveCustomCast($value, $castType),
            };
        } catch (\Throwable $exception) {
            throw new \Exception("Value of [$this->__property_name] are invalid. " . $exception->getMessage());
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function resolveCustomCast($value, $castType): mixed
    {
        $reflection = new ReflectionClass($castType);
        if ($reflection->implementsInterface(CastInterface::class)) {
            return $reflection->newInstance()->handle($value);
        }

        return $value;
    }

    private function castToEnum(mixed $value, string $enumClass): ?UnitEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (! enum_exists($enumClass)) {
            return null;
        }

        if (is_a($enumClass, BackedEnum::class, true)) {
            return $enumClass::tryFrom($value);
        }

        // For pure UnitEnum (try to match by name)
        if (is_string($value)) {
            $value = strtoupper($value);
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }

        return null;
    }

    private function castToDateTime(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            try {
                return new CarbonImmutable($value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
