<?php

declare(strict_types=1);

namespace Talanov\DataTransfer;

use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use Talanov\DataTransfer\Attributes\Cast;
use Talanov\DataTransfer\Attributes\MapInputName;
use Talanov\DataTransfer\Concerns\CastInterface;
use Talanov\DataTransfer\Traits\ResponseTrait;
use Throwable;
use UnitEnum;

abstract class DataTransferObject implements JsonSerializable
{
    use ResponseTrait;

    protected ?ReflectionClass $__reflection = null;

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

    protected function getReflection(): ReflectionClass
    {
        if (!isset($this->__reflection)) {
            $this->__reflection = new ReflectionClass($this);
        }

        return $this->__reflection;
    }

    private function castAndFill(array $data): void
    {
        foreach ($this->getReflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $inputNameAttrs = $property->getAttributes(MapInputName::class);
            $inputKey = $inputNameAttrs !== [] ? $inputNameAttrs[0]->newInstance()->name : $propertyName;
            $value = $data[$inputKey] ?? null;

            $castAttrs = $property->getAttributes(Cast::class);
            $castAttribute = $castAttrs !== [] ? $castAttrs[0] : null;

            if ($castAttribute) {
                /** @var Cast $cast */
                $cast = $castAttribute->newInstance();
                $value = $this->castValue($value, $cast->type, $cast->default, $propertyName);
            } elseif ($property->hasType()) {
                $value = $this->castToNativeType($value, $property->getType(), $propertyName);
            }

            if (!$property->isInitialized($this) || $value !== null || $property->getType()?->allowsNull()) {
                $property->setValue($this, $value);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function castValue(mixed $value, string|UnitEnum $castType, mixed $default = null, ?string $propertyName = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        if (is_string($castType) && enum_exists($castType)) {
            return $this->castToEnum($value, $castType);
        }

        return $this->caster($value, $castType, $propertyName);
    }

    private function castToNativeType(mixed $value, ReflectionType $type, ?string $propertyName = null): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (enum_exists($typeName)) {
                return $this->castToEnum($value, $typeName);
            }

            return $this->caster($value, $typeName, $propertyName);
        }

        return $value;
    }

    private function caster(mixed $value, mixed $castType, ?string $propertyName = null): mixed
    {
        try {
            return match ($castType) {
                'int', 'integer' => (int)$value,
                'abs_int', 'abs_integer' => abs((int)$value),
                'float', 'double' => (float)$value,
                'abs_float', 'abs_double' => abs((float)$value),
                'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'string' => (string)$value,
                'array' => (array)$value,
                'datetime' => $this->castToDateTime($value),
                default => $this->resolveCustomCast($value, $castType),
            };
        } catch (Throwable $exception) {
            throw new Exception("Value of [$propertyName] is invalid. " . $exception->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     */
    private function resolveCustomCast(mixed $value, mixed $castType): mixed
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

        if (!enum_exists($enumClass)) {
            return null;
        }

        if (is_a($enumClass, BackedEnum::class, true)) {
            return $enumClass::tryFrom($value);
        }

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
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }
}
