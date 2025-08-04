<?php

namespace App\Entity;

use ReflectionMethod;

abstract class BaseEntity
{
    /**
     * Fill the entity with data from an array
     *
     * @param array $data Array of field/value pairs
     * @return static
     */
    public function fill(array $data): static
    {
        foreach ($data as $field => $value) {
            $setterMethod = 'set' . ucfirst($field);

            if (method_exists($this, $setterMethod)) {
                $reflectionMethod = new ReflectionMethod($this, $setterMethod);
                $parameters = $reflectionMethod->getParameters();

                if (count($parameters) > 0) {
                    $parameter = $parameters[0];
                    $parameterType = $parameter->getType();

                    // Convert value based on parameter type
                    $convertedValue = $this->convertValue($value, $parameterType);

                    $this->$setterMethod($convertedValue);
                }
            }
        }

        return $this;
    }

    /**
     * Convert value to the expected type
     *
     * @param mixed $value
     * @param \ReflectionType|null $type
     * @return mixed
     */
    private function convertValue(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($type === null) {
            return $value;
        }

        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Handle nullable types
        if ($type->allowsNull() && $value === null) {
            return null;
        }

        return match ($typeName) {
            'bool' => $this->convertToBool($value),
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value
        };
    }

    /**
     * Convert value to boolean
     *
     * @param mixed $value
     * @return bool
     */
    private function convertToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lowerValue = strtolower($value);
            return in_array($lowerValue, ['true', '1', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return false;
    }
}
