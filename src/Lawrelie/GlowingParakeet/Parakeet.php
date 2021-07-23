<?php
namespace Lawrelie\GlowingParakeet;
use DateTimeZone, DateTimeInterface, DomainException, PDO, Throwable;
class Parakeet {
    use ReadProperties;
    public function __construct(iterable $properties, private string $charset = 'UTF-8') {
        if (false === \ini_set('default_charset', $this->charset) || false === \mb_internal_encoding($this->charset)) {
            throw new DomainException;
        }
        $this->setReadableProperties($properties);
        $this->datetime;
        if (!$this->index || !$this->current) {
            throw new DomainException;
        }
    }
    public function createDateTime(string $datetime = 'now'): DateTimeInterface {
        return \date_create_immutable($datetime, $this->timezone)->setTimezone($this->timezone);
    }
    public function createIndex(iterable $properties, string $className = Contents\Index::class): Contents\Index {
        return new $className($properties, $this);
    }
    protected function readProperty_charset(): string {
        return $this->charset;
    }
    protected function readProperty_current(): ?Contents\Contents {
        $index = $this->index;
        $queryKeys = $this->queryKeys;
        try {
            $current = $index->query($this->sanitizeString($_GET[$queryKeys['contents']]));
            if (!!$current) {
                return $current;
            }
        } catch (Throwable) {}
        try {
            $current = $index->dateArchives->query($this->sanitizeString($_GET[$queryKeys['date']]));
            if (!!$current) {
                return $current;
            }
        } catch (Throwable) {}
        try {
            $current = $index->searchResults->query($this->sanitizeString($_GET[$queryKeys['search']]));
            if (!!$current) {
                return $current;
            }
        } catch (Throwable) {}
        return $index;
    }
    protected function readProperty_datetime(): DateTimeInterface {
        return $this->createDateTime();
    }
    protected function readProperty_db(mixed $var): ?PDO {
        if (!($var instanceof PDO)) {
            return null;
        }
        $var->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        try {
            $var->beginTransaction();
            $var->exec('CREATE TABLE IF NOT EXISTS lgp_contents (lgp_id TEXT PRIMARY KEY UNIQUE)');
            $columns = [
                'TEXT PRIMARY KEY UNIQUE' => ['id'],
                'TEXT DEFAULT NULL' => ['author', 'children', 'content', 'date', 'description', 'mtime', 'name', 'tags'],
            ];
            foreach ($columns as $def => $names) {
                foreach ($names as $name) {
                    try {
                        $var->exec("ALTER TABLE lgp_contents ADD lgp_$name $def");
                    } catch (Throwable) {}
                }
            }
            $var->commit();
        } catch (Throwable) {
            $var->rollBack();
        }
        return $var;
    }
    protected function readProperty_dev(mixed $var): bool {
        return $this->sanitizeBoolean($var);
    }
    protected function readProperty_directories(mixed $var): array {
        $keys = ['archives', 'date', 'search', 'tags', 'users'];
        return \array_filter($this->sanitizeStrings($var), fn(string $v): bool => '' !== $v) + \array_combine($keys, $keys);
    }
    protected function readProperty_format(mixed $var): array {
        return \array_filter($this->sanitizeFunctions($var), fn(?callable $v): bool => !!$v) + [
            'date' => fn(DateTimeInterface $datetime): string => $datetime->format('j F Y'),
            'day' => fn(DateTimeInterface $datetime): string => $datetime->format('j'),
            'month' => fn(DateTimeInterface $datetime): string => $datetime->format('F'),
            'queryResult' => fn(int $result): string => \number_format($result, 0) . ' result' . (1 < $result ? 's' : ''),
            'year' => fn(DateTimeInterface $datetime): string => $datetime->format('Y'),
        ];
    }
    protected function readProperty_index(mixed $var): ?Contents\Index {
        try {
            return $this->createIndex($this->readProperties($var));
        } catch (Throwable) {}
        return null;
    }
    protected function readProperty_page(): int {
        try {
            $page = $this->sanitizeInteger($_GET[$this->queryKeys['page']]);
            return 1 > $page ? 1 : $page;
        } catch (Throwable) {}
        return 1;
    }
    protected function readProperty_queryKeys(mixed $var): array {
        $keys = ['contents', 'date', 'page', 'search'];
        return \array_filter($this->sanitizeStrings($var), fn(string $v): bool => '' !== $v) + \array_combine($keys, $keys);
    }
    protected function readProperty_url(mixed $var): string {
        return $this->sanitizeString($var);
    }
    protected function readProperty_timezone(mixed $var): ?DateTimeZone {
        try {
            return $var instanceof DateTimeZone ? $var : \timezone_open($this->sanitizeString($var));
        } catch (Throwable) {}
        return null;
    }
}
