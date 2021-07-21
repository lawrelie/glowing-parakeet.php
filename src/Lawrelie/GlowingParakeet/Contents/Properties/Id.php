<?php
namespace Lawrelie\GlowingParakeet\Contents\Properties;
use DomainException;
class Id extends Property implements \Stringable {
    const SEPARATOR = '/';
    public function __construct(private string $id, mixed ...$args) {
        $this->id = $this->normalize($this->id);
        if ('' === $this->id) {
            throw new DomainException;
        }
        parent::__construct([], ...$args);
    }
    public function __toString(): string {
        return $this->id;
    }
    public function normalize(string $id): string {
        return \trim(\mb_strtolower(\mb_convert_kana(\preg_replace('/\s+/u', '', $id), 'aCKV')), self::SEPARATOR);
    }
    protected function readProperty_fromIndex(): string {
        return $this->relativeFrom($this->parakeet->index->id);
    }
    protected function readProperty_fromParent(): string {
        return $this->relativeFrom($this->contents->parent->id);
    }
    protected function readProperty_id(): string {
        return $this->id;
    }
    public function relative(string $from, string $to): string {
        $prefix = $from;
        if ($prefix === $to) {
            return '';
        }
        $prefix .= self::SEPARATOR;
        if (\str_starts_with($to, $prefix)) {
            return \substr($to, \strlen($prefix));
        }
        throw new DomainException;
    }
    public function relativeFrom(string $from): string {
        return $this->relative($this->normalize($from), $this->id);
    }
    public function relativeTo(string $to): string {
        return $this->relative($this->id, $this->normalize($to));
    }
}
