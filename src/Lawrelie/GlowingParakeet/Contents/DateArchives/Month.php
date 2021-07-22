<?php
namespace Lawrelie\GlowingParakeet\Contents\DateArchives;
use Lawrelie\GlowingParakeet as lgp;
use DateTimeInterface, DomainException, Throwable;
class Month extends DateArchive {
    private array $children = [];
    public function __get(string $name): mixed {
        $parent = parent::__get($name);
        return 'children' === $name ? [...$parent, ...$this->$name] : $parent;
    }
    public function createChild(mixed $datetime, ?string $className = Day::class): lgp\Contents\Contents {
        $day = parent::createChild($datetime, $className);
        if ($day instanceof Day) {
            return $day;
        }
        throw new DomainException;
    }
    public function query(string|DateTimeInterface $query): ?lgp\Contents\Contents {
        try {
            if (empty($query)) {
                throw new DomainException;
            }
            $datetime = $query instanceof DateTimeInterface ? $query : $this->parakeet->createDateTime($query);
            try {
                $day = $this->children[(int) $datetime->format('d')];
            } catch (Throwable) {
                $day = $this->createChild($datetime);
                $this->children[(int) $day->id->fromParent] = $day;
            }
            return $day;
        } catch (Throwable) {}
        return parent::query($query);
    }
    protected function readProperty_dateTime(mixed $var): ?\DateTimeInterface {
        $datetime = parent::readProperty_dateTime($var);
        return match (!$datetime) {
            true => $this->parakeet->createDateTime(\sprintf('%s-%02d-01', $this->parent->dateTime->format('Y'), $this->sanitizeInteger($var))),
            default => $datetime,
        };
    }
    protected function readProperty_id(mixed ...$args): lgp\Contents\Properties\Id {
        return parent::readProperty_id((int) $this->dateTime->format('m'));
    }
    protected function readProperty_name(mixed ...$args): string {
        return $this->parakeet->format['month']($this->dateTime);
    }
}
