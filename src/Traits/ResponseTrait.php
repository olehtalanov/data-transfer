<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Traits;

use ReflectionProperty;
use Talanov\DataTransfer\Attributes\Hidden;
use Talanov\DataTransfer\Attributes\MapOutputName;

trait ResponseTrait
{
    protected array $__extras = [];

    public function __isset(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->__extras);
    }

    public function __get(string $propertyName): mixed
    {
        if (array_key_exists($propertyName, $this->__extras)) {
            return $this->__extras[$propertyName];
        }

        return null;
    }

    public function merge(array $data): static
    {
        $this->__extras = array_merge($this->__extras, $data);

        return $this;
    }

    public function mergeWhen(bool $condition, array $data): static
    {
        if ($condition) {
            $this->merge($data);
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->transform();
    }

    public function jsonSerialize(): array
    {
        return $this->transform();
    }

    public function toJson(int $options = 0): false|string
    {
        return json_encode($this->toArray(), $options);
    }

    protected function transform(): array
    {
        $params = [];
        foreach ($this->getReflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(Hidden::class)) {
                continue;
            }

            $name = $property->getName();

            $outputName = $property->getName();
            $attributes = $property->getAttributes(MapOutputName::class);
            foreach ($attributes as $attribute) {
                $outputName = $attribute->getArguments()[0];
            }

            $params[$outputName] = $this->$name ?? null;
        }

        return array_merge($params, $this->__extras);
    }
}
