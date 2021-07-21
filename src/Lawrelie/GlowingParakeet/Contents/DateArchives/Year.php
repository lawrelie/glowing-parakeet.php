<?php
namespace Lawrelie\GlowingParakeet\Contents\DateArchives;
use Lawrelie\GlowingParakeet as lgp;
use DateTimeInterface, Throwable;
class Year extends DateArchive {
    private array $children = [];
    public function __get(string $name): mixed {
        $parent = parent::__get($name);
        return 'children' === $name ? [...$parent, ...$this->children] : $parent;
    }
    public function createChild(mixed $datetime, ?string $className = Month::class): lgp\Contents\Contents {
        $month = parent::createChild($datetime, $className);
        if ($month instanceof Month) {
            return $month;
        }
        throw new \DomainException;
    }
    public function query(string|DateTimeInterface $query): ?lgp\Contents\Contents {
        try {
            $datetime = $query instanceof DateTimeInterface ? $query : $this->parakeet->createDateTime($query);
            try {
                $month = $this->children[(int) $datetime->format('m')];
            } catch (Throwable) {
                $month = $this->createChild($datetime);
                $this->children[(int) $month->id->fromParent] = $month;
            }
            return $month->query($datetime);
        } catch (Throwable) {}
        return parent::query($query);
    }
    protected function readProperty_dateTime(mixed $var): ?\DateTimeInterface {
        $datetime = parent::readProperty_dateTime($var);
        return !$datetime ? $this->parakeet->createDateTime(\sprintf('%04d-01-01', $this->sanitizeInteger($var))) : $datetime;
    }
    protected function readProperty_id(mixed ...$args): lgp\Contents\Properties\Id {
        return parent::readProperty_id((int) $this->dateTime->format('Y'));
    }
    protected function readProperty_name(mixed ...$args): string {
        return $this->parakeet->format['year']($this->dateTime);
    }
}
