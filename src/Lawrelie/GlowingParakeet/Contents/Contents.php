<?php
namespace Lawrelie\GlowingParakeet\Contents;
use Lawrelie\GlowingParakeet as lgp;
use DateTimeInterface, DomainException, Throwable;
class Contents {
    use lgp\ReadProperties;
    public function __construct(iterable $properties, private lgp\Parakeet $parakeet, private ?self $parent = null) {
        $this->setReadableProperties($properties);
        $idFromParent = null;
        try {
            $idFromParent = $this->id->fromParent;
        } catch (Throwable) {}
        if ('' === $idFromParent) {
            throw new DomainException;
        }
    }
    public function createChild(iterable $properties, ?string $className = null): self {
        $args = [$properties, $this->parakeet, $this];
        return !\is_null($className) ? new $className(...$args) : new static(...$args);
    }
    public function createId(string $id, string $className = Properties\Id::class): Properties\Id {
        return new $className($id, $this);
    }
    public function inherit(string $name): mixed {
        $value = $this->__get($name);
        try {
            return !empty($value) ? $value : $this->parent->inherit($name);
        } catch (Throwable) {}
        return $value;
    }
    public function is(self $contents): bool {
        return (string) $this->id === (string) $contents->id;
    }
    public function isAncestorOf(self $contents): bool {
        return \str_starts_with($contents->id, $this->id . Properties\Id::SEPARATOR);
    }
    public function isChildOf(self $contents): bool {
        try {
            return (string) $contents->id === (string) $this->parent->id;
        } catch (Throwable) {}
        return false;
    }
    public function isDescendantOf(self $contents): bool {
        return \str_starts_with($this->id, $contents->id . Properties\Id::SEPARATOR);
    }
    public function isParentOf(self $contents): bool {
        try {
            return (string) $contents->parent->id === (string) $this->id;
        } catch (Throwable) {}
        return false;
    }
    public function isSiblingOf(self $contents): bool {
        try {
            return $this->parent->isAncestorOf($contents);
        } catch (Throwable) {}
        return false;
    }
    public function normalizeQuery(string $query): string {
        return \preg_replace('/\s+(?=\s)/u', '', \mb_strtolower(\trim(\mb_convert_kana($query, 'asCKV'))));
    }
    public function query(string $query): ?self {
        $id = $this->id . Properties\Id::SEPARATOR . $this->id->normalize($query);
        foreach ($this->__get('children') as $child) {
            try {
                $relative = $child->id->relativeTo($id);
            } catch (DomainException) {
                continue;
            }
            if ('' === $relative) {
                return $child;
            }
            $descendant = $child->query($relative);
            return !$descendant ? $child : $descendant;
        }
        return null;
    }
    public function queryDate(string|DateTimeInterface $datetime): ?DateArchives\Day {
        return $this->parakeet->index->dateArchives->query($datetime);
    }
    public function queryFromQuery(array $rows): array {
        $contents = [];
        $index = $this->parakeet->index;
        $delete = null;
        try {
            $delete = $this->parakeet->db->prepare('DELETE FROM lgp_contents WHERE lgp_id = ?');
        } catch (Throwable) {}
        foreach ($rows as $row) {
            try {
                $result = $index->query($index->id->relativeTo($row['lgp_id']));
                if ((string) $result->id === $row['lgp_id']) {
                    $contents[] = $result;
                    continue;
                }
            } catch (Throwable) {}
            try {
                $delete->execute([$row['lgp_id']]);
                $delete->closeCursor();
            } catch (Throwable) {}
        }
        return $contents;
    }
    protected function readProperty_ancestors(): array {
        try {
            return [...$this->parent->ancestors, $this->parent];
        } catch (Throwable) {}
        return [];
    }
    protected function readProperty_author(mixed $var): ?Regular {
        $author = $this->parakeet->index->userDirectory->query($this->sanitizeString($var));
        return !$author ? $this->parent?->author : $author;
    }
    protected function readProperty_children(mixed $var): array {
        $children = [];
        foreach ($this->sanitizeIterator($var) as $v) {
            try {
                $child = $this->createChild($this->readProperties($v));
                $children[(string) $child->id] = $child;
            } catch (Throwable) {}
        }
        return $this->sortContents($children);
    }
    protected function readProperty_compareContents(): callable {
        $order = $this->order;
        if (!\in_array($order, ['ASC', 'DESC'], true)) {
            return fn(self $a, self $b): int => 0;
        }
        $orderby = $this->orderby;
        $functions = match ($orderby) {
            'id', 'name', 'description' => [
                'compare' => '\strnatcasecmp',
                'format' => fn(self $contents): string => $this->normalizeQuery($contents->$orderby),
            ],
            'author' => [
                'compare' => '\strnatcasecmp',
                'format' => fn(self $contents): string => $this->normalizeQuery($contents->$orderby->name),
            ],
            'children' => [
                'compare' => fn(float $a, float $b): int => $a - $b,
                'format' => fn(self $contents): int => \count($contents->children),
            ],
            'date' => [
                'compare' => fn(?int $a, ?int $b): int => !\is_null($a) ? (!\is_null($b) ? $a - $b : -1) : (!\is_null($b) ? 1 : 0),
                'format' => fn(self $contents): ?int => $contents->date?->dateTime->getTimestamp(),
            ],
            default => null,
        };
        if (!$functions) {
            return fn(self $a, self $b): int => 0;
        }
        $r = 'ASC' === $order ? 1 : -1;
        return fn(self $a, self $b): int => $functions['compare']($functions['format']($a), $functions['format']($b)) * $r;
    }
    protected function readProperty_content(mixed $var): ?string {
        $filename = $this->sanitizeString($var);
        if ('' === $filename || !\file_exists($filename) || \is_dir($filename)) {
            return null;
        }
        $content = null;
        switch (\strtolower(\pathinfo($filename, \PATHINFO_EXTENSION))) {
            case 'html':
            case 'htm':
                $content = \file_get_contents($filename);
                return false === $content ? null : $content;
        }
        return null;
    }
    protected function readProperty_date(mixed $var): ?DateArchives\Day {
        return $var instanceof DateArchives\Day ? $var : $this->queryDate($var instanceof DateTimeInterface ? $var : $this->sanitizeString($var));
    }
    protected function readProperty_description(mixed $var): string {
        return $this->sanitizeString($var);
    }
    protected function readProperty_id(mixed $var): Properties\Id {
        $parent = '';
        try {
            $parent .= $this->parent->id . Properties\Id::SEPARATOR;
        } catch (Throwable) {}
        return $this->createId($parent . $this->sanitizeString($var));
    }
    protected function readProperty_isArchive(): bool {
        $archives = $this->parakeet->index->archiveDirectory;
        return $this->is($archives) || $this->isDescendantOf($archives);
    }
    protected function readProperty_isIndex(): bool {
        try {
            return '' === $this->id->fromIndex;
        } catch (Throwable) {}
        return false;
    }
    protected function readProperty_isTag(): bool {
        $tags = $this->parakeet->index->tagDirectory;
        return $this->is($tags) || $this->isDescendantOf($tags);
    }
    protected function readProperty_isUser(): bool {
        $users = $this->parakeet->index->userDirectory;
        return $this->is($users) || $this->isDescendantOf($users);
    }
    protected function readProperty_mtime(mixed $var): ?DateTimeInterface {
        if ($var instanceof DateTimeInterface) {
            return $var->setTimezone($this->parakeet->timezone);
        }
        $datetime = $this->sanitizeString($var);
        if (!empty($datetime) && !\is_numeric($datetime)) {
            try {
                return $this->parakeet->createDateTime($datetime);
            } catch (Throwable) {}
        }
        return $this->parent?->mtime;
    }
    protected function readProperty_name(mixed $var): string {
        return $this->sanitizeString($var);
    }
    protected function readProperty_next(): ?self {
        $prev = null;
        foreach ($this->siblings as $sibling) {
            if ($prev?->is($this)) {
                return $sibling;
            }
            $prev = $sibling;
        }
        return null;
    }
    protected function readProperty_order(mixed $var): string {
        $order = \mb_convert_kana($this->sanitizeString($var), 'a');
        if (!\strcasecmp('asc', $order) || !\strcasecmp('desc', $order)) {
            return \strtoupper($order);
        }
        try {
            return !\strcasecmp('custom', $order) ? \strtolower($order) : $this->parent->order;
        } catch (Throwable) {}
        return 'custom';
    }
    protected function readProperty_orderby(mixed $var): string {
        $orderby = $this->sanitizeString($var);
        try {
            return '' === $orderby ? $this->parent->orderby : $orderby;
        } catch (Throwable) {}
        return 'custom';
    }
    protected function readProperty_parakeet(): lgp\Parakeet {
        return $this->parakeet;
    }
    protected function readProperty_parent(): ?self {
        return $this->parent;
    }
    protected function readProperty_prev(): ?self {
        $prev = null;
        foreach ($this->siblings as $sibling) {
            if ($this->is($sibling)) {
                break;
            }
            $prev = $sibling;
        }
        return $prev;
    }
    protected function readProperty_query(): array {
        $id = \addcslashes($this->id, '%_\\');
        $idPrefix = $id . \addcslashes(Properties\Id::SEPARATOR, '%_\\') . '%';
        list($where, $params) = match (true) {
            $this->isTag => ['lgp_tags LIKE :id', [':id' => "%$id%"]],
            $this->isUser => ['lgp_author IS :id OR lgp_author LIKE :id_prefix', [':id' => $id, ':id_prefix' => $idPrefix]],
            default => ['lgp_id LIKE :id_prefix', [':id_prefix' => $idPrefix]],
        };
        try {
            $select = $this->parakeet->db->prepare(
                "SELECT lgp_id FROM lgp_contents
                WHERE ($where) AND (lgp_children ISNULL OR lgp_children IS :empty) AND (lgp_date NOT NULL AND lgp_date IS NOT :empty)
                ORDER BY lgp_date DESC",
            );
            $select->execute($params + [':empty' => '']);
            return $select->fetchAll();
        } catch (Throwable) {}
        return [];
    }
    protected function readProperty_queryKey(): string {
        return $this->parakeet->queryKeys['contents'];
    }
    protected function readProperty_queryPerPage(mixed $var): int {
        $per = $this->sanitizeInteger($var);
        try {
            return 1 > $per ? $this->parent->queryPerPage : $per;
        } catch (Throwable) {}
        return 4;
    }
    protected function readProperty_siblings(): array {
        try {
            return $this->parent->children;
        } catch (Throwable) {}
        return [];
    }
    protected function readProperty_tags(mixed $var): array {
        $tags = [];
        try {
            foreach ($this->parent->tags as $tag) {
                try {
                    $tags[(string) $tag->id] = $tag;
                } catch (Throwable) {}
            }
        } catch (Throwable) {}
        $directory = $this->parakeet->index->tagDirectory;
        foreach ($this->sanitizeStrings($var) as $v) {
            $tag = $directory->query($v);
            try {
                $tags[(string) $tag->id] = $tag;
            } catch (Throwable) {}
        }
        return $directory->sortContents($tags);
    }
    public function sortContents(array $contents): array {
        return !\usort($contents, $this->compareContents) ? [] : $contents;
    }
}
