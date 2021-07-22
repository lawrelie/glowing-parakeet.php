<?php
namespace Lawrelie\GlowingParakeet\Contents\DateArchives;
use Lawrelie\GlowingParakeet as lgp;
use DateTimeInterface, Throwable;
class DateArchive extends lgp\Contents\Contents {
    private array $children = [];
    public function __construct(...$args) {
        parent::__construct(...$args);
        if (!$this->dateTime) {
            throw new \DomainException;
        }
    }
    public function __get(string $name): mixed {
        return 'children' === $name ? $this->$name : parent::__get($name);
    }
    public function createChild(mixed $datetime, ?string $className = self::class): parent {
        $archive = parent::createChild(['dateTime' => $datetime], $className);
        if ($archive instanceof self) {
            return $archive;
        }
        throw new \DomainException;
    }
    public function query(string $query): ?parent {
        $datetime = \explode(lgp\Contents\Properties\Id::SEPARATOR, $this->id->normalize($query));
        $number = $this->sanitizeInteger($datetime[0]);
        try {
            $archive = $this->children[$number];
        } catch (Throwable) {
            try {
                $archive = $this->createChild($number);
            } catch (Throwable) {
                return null;
            }
            $this->children[(int) $archive->id->fromParent] = $archive;
        }
        \array_shift($datetime);
        if (!$datetime) {
            return $archive;
        }
        $descendant = $archive->query(\implode(lgp\Contents\Properties\Id::SEPARATOR, $datetime));
        return !$descendant ? $archive : $descendant;
    }
    protected function readProperty_dateTime(mixed $var): ?DateTimeInterface {
        try {
            return $var;
        } catch (Throwable) {}
        return null;
    }
    protected function readProperty_name(mixed ...$args): string {
        return $this->parakeet->format['date']($this->dateTime);
    }
    protected function readProperty_queryKey(): string {
        return $this->parakeet->queryKeys['date'];
    }
}
