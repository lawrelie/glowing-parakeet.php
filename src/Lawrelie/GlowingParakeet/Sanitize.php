<?php
namespace Lawrelie\GlowingParakeet;
use Throwable;
trait Sanitize {
    public function sanitizeArray(mixed $var, ?callable $read = null): array {
        if (!\is_array($var)) {
            if ($var instanceof \Traversable) {
                $var = \iterator_to_array($var);
            } else {
                try {
                    $var = (array) $var;
                } catch (Throwable $e) {
                    return [];
                }
            }
        }
        if (!$read) {
            return $var;
        }
        $array = [];
        foreach ($var as $k => $v) {
            $array[$k] = $read($v);
        }
        return $array;
    }
    public function sanitizeArrays(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeArray']);
    }
    public function sanitizeBoolean(mixed $var): bool {
        try {
            return \filter_var($var, \FILTER_VALIDATE_BOOL);
        } catch (Throwable $e) {}
        return false;
    }
    public function sanitizeBooleans(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeBoolean']);
    }
    public function sanitizeFloat(mixed $var): float {
        try {
            return (float) \filter_var($var, \FILTER_SANITIZE_NUMBER_FLOAT, \FILTER_FLAG_ALLOW_FRACTION | \FILTER_FLAG_ALLOW_SCIENTIFIC);
        } catch (Throwable $e) {}
        return 0;
    }
    public function sanitizeFloats(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeFloat']);
    }
    public function sanitizeFunction(mixed $var): ?callable {
        try {
            return $var;
        } catch (Throwable $e) {}
        return null;
    }
    public function sanitizeFunctions(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeFunction']);
    }
    public function sanitizeInteger(mixed $var): int {
        try {
            return (int) $this->sanitizeFloat($var);
        } catch (Throwable $e) {}
        return 0;
    }
    public function sanitizeIntegers(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeInteger']);
    }
    public function sanitizeIterator(mixed $var): iterable {
        return !\is_iterable($var) ? $this->sanitizeArray($var) : $var;
    }
    public function sanitizeIterators(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeIterator']);
    }
    public function sanitizeNumber(mixed $var): float|int|string {
        if (\is_numeric($var)) {
            return $var;
        }
        try {
            $float = \filter_var($var, \FILTER_SANITIZE_NUMBER_FLOAT, \FILTER_FLAG_ALLOW_FRACTION | \FILTER_FLAG_ALLOW_SCIENTIFIC);
            if (\is_numeric($float)) {
                return $float;
            }
        } catch (Throwable $e) {}
        return $this->sanitizeFloat($var);
    }
    public function sanitizeNumbers(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeNumber']);
    }
    public function sanitizeObject(mixed $var): ?object {
        try {
            return (object) $var;
        } catch (Throwable $e) {}
        return null;
    }
    public function sanitizeObjects(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeObject']);
    }
    public function sanitizeResource(mixed $var): mixed {
        return !\is_resource($var) ? null : $var;
    }
    public function sanitizeResources(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeResource']);
    }
    public function sanitizeScalar(mixed $var): bool|float|int|string {
        return !\is_scalar($var) ? 0 : $var;
    }
    public function sanitizeScalars(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeScalar']);
    }
    public function sanitizeString(mixed $var): string {
        try {
            return (string) $var;
        } catch (Throwable $e) {}
        return '';
    }
    public function sanitizeStrings(mixed $var): array {
        return $this->sanitizeArray($var, [$this, 'sanitizeString']);
    }
}
