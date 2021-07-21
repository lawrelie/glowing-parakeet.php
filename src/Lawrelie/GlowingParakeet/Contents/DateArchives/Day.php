<?php
namespace Lawrelie\GlowingParakeet\Contents\DateArchives;
use Lawrelie\GlowingParakeet as lgp;
class Day extends DateArchive {
    protected function readProperty_date(mixed ...$args): ?self {
        return $this;
    }
    protected function readProperty_dateTime(mixed $var): ?\DateTimeInterface {
        $datetime = parent::readProperty_dateTime($var);
        return match (!$datetime) {
            true => $this->parakeet->createDateTime(\sprintf('%s-%02d', $this->parent->dateTime->format('Y-m'), $this->sanitizeInteger($var))),
            default => $datetime,
        };
    }
    protected function readProperty_id(mixed ...$args): lgp\Contents\Properties\Id {
        return parent::readProperty_id((int) $this->dateTime->format('d'));
    }
    protected function readProperty_name(mixed ...$args): string {
        return $this->parakeet->format['day']($this->dateTime);
    }
}
