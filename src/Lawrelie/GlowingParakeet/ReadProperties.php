<?php
namespace Lawrelie\GlowingParakeet;
use Throwable;
trait ReadProperties {
    use Sanitize;
    private array $readableProperties_unread = [];
    private array $readableProperties_already = [];
    public function __get(string $name): mixed {
        if (\array_key_exists($name, $this->readableProperties_already)) {
            return $this->readableProperties_already[$name];
        }
        $value = null;
        try {
            $value = $this->readableProperties_unread[$name];
        } catch (Throwable) {}
        try {
            $this->readableProperties_already[$name] = [$this, 'readProperty_' . $name]($value);
        } catch (Throwable) {
            $i = \explode('_', $name)[0];
            $type = 'string';
            foreach (['array', 'boolean', 'float', 'function', 'integer', 'iterator', 'number', 'object', 'resource', 'scalar', 'string'] as $v) {
                if ($v === $i || $v . 's' === $i) {
                    $type = $i;
                    break;
                }
            }
            $this->readableProperties_already[$name] = $type === $i || \array_key_exists($name, $this->readableProperties_unread) ? [$this, 'sanitize' . \ucfirst($type)]($value) : null;
        }
        return $this->readableProperties_already[$name];
    }
    public function __isset(string $name): bool {
        return !\is_null($this->__get($name));
    }
    public function includeProperties(string $filename): iterable {
        if (!\file_exists($filename) || \is_dir($filename)) {
            throw new \DomainException;
        }
        return ['mtime' => \date_create_immutable(\date('c', \filemtime($filename)))] + $this->sanitizeArray(include $filename);
    }
    public function readProperties(mixed $var): iterable {
        if (!\is_iterable($var)) {
            try {
                return $this->includeProperties($this->sanitizeString($var));
            } catch (Throwable) {}
            return $this->sanitizeIterator($var);
        }
        return $var;
    }
    public function setReadableProperties(iterable $properties): void {
        foreach ($properties as $name => $value) {
            $this->readableProperties_unread[$name] = $value;
        }
    }
}
