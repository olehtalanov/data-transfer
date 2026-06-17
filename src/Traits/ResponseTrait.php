<?php

declare(strict_types=1);

namespace Talanov\DataTransfer\Traits;

use ReflectionClass;
use ReflectionProperty;
use Talanov\DataTransfer\Attributes\Hidden;
use Talanov\DataTransfer\Attributes\MapOutputName;

trait ResponseTrait
{
    protected array $__extras = [];

    public function __get(string $propertyName): mixed
    {
        if (array_key_exists($propertyName, $this->__extras)) {
            return $this->__extras[$propertyName];
        }

        return null;
    }

    public function merge(mixed $data): static
    {
        $this->__extras = array_merge($this->__extras, $data);

        return $this;
    }

    public function mergeWhen(bool $condition, mixed $data): static
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
        return $this->toArray();
    }

    public function toJson($options = 0): false|string
    {
        return json_encode($this->toArray(), $options);
    }

    protected function transform(): array
    {
        $reflection = new ReflectionClass($this);

        $params = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
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

        return get_object_vars((object) array_merge($params, $this->__extras));

        //        return get_object_vars((object) json_decode(json_encode(array_merge($params, $this->_extras))));
    }
}
