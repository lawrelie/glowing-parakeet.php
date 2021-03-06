<?php
namespace Lawrelie\GlowingParakeet\Contents\DateArchives;
use Lawrelie\GlowingParakeet as lgp;
use DateTimeInterface, DomainException, Throwable;
class DateArchives extends lgp\Contents\Contents {
    private array $children = [];
    public function __get(string $name): mixed {
        return 'children' === $name ? $this->$name : parent::__get($name);
    }
    public function createChild(mixed $datetime, ?string $className = Year::class): parent {
        $year = parent::createChild(['dateTime' => $datetime], $className);
        if ($year instanceof Year) {
            return $year;
        }
        throw new DomainException;
    }
    public function query(string|DateTimeInterface $query): ?parent {
        try {
            if (empty($query) || \is_numeric($query)) {
                throw new DomainException;
            }
            $datetime = $query instanceof DateTimeInterface ? $query : $this->parakeet->createDateTime($query);
            try {
                $year = $this->children[$datetime->format('c')];
            } catch (Throwable) {
                $year = $this->createChild($datetime);
                $this->children[$datetime->format('c')] = $year;
            }
            return $year->query($datetime);
        } catch (Throwable) {}
        $datetime = \explode(lgp\Contents\Properties\Id::SEPARATOR, $this->id->normalize($query));
        try {
            $archive = $this->createChild($this->sanitizeInteger($datetime[0]));
        } catch (Throwable) {
            return null;
        }
        $this->children[$archive->dateTime->format('c')] = $archive;
        \array_shift($datetime);
        if (!$datetime) {
            return $archive;
        }
        $month = $archive->query(\implode(lgp\Contents\Properties\Id::SEPARATOR, $datetime));
        return !$month ? $archive : $month;
    }
}
