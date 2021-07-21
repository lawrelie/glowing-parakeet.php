<?php
namespace Lawrelie\GlowingParakeet\Contents;
use DomainException, PDO, Throwable;
class Regular extends Contents {
    private bool $inserted = false;
    private ?\DateTimeInterface $mtime_content = null;
    public function __construct(mixed ...$args) {
        parent::__construct(...$args);
        $this->insert();
    }
    public function insert(): bool {
        if ($this->inserted) {
            return false;
        }
        $this->inserted = true;
        $db = $this->parakeet->db;
        if (!$db) {
            return false;
        }
        $inTransaction = $db->inTransaction();
        try {
            match ($inTransaction) {true => false, default => $db->beginTransaction()};
            $select = $db->prepare('SELECT lgp_id, lgp_mtime FROM lgp_contents WHERE lgp_id = ?');
            $id = (string) $this->id;
            $select->execute([$id]);
            if (!$select->fetch()) {
                $db->prepare('INSERT INTO lgp_contents (lgp_id) VALUES (?)')->execute([$id]);
            }
            $parakeet = $this->parakeet;
            try {
                $select->closeCursor();
                $select->execute([$id]);
                $mtime = $select->fetch()['lgp_mtime'];
                $mtime = !empty($mtime) ? $parakeet->createDateTime($this->sanitizeString($mtime)) : null;
            } catch (Throwable) {
                $mtime = null;
            }
            $content = $this->content;
            if (!!$mtime && 1 === $this->mtime?->diff($mtime)->invert && 1 === $this->mtime_content?->diff($mtime)->invert) {
                throw new DomainException;
            }
            $update = $db->prepare(
                'UPDATE lgp_contents
                SET lgp_author = :author,
                    lgp_children = :children,
                    lgp_content = :content,
                    lgp_date = :date,
                    lgp_description = :description,
                    lgp_mtime = :mtime,
                    lgp_name = :name,
                    lgp_tags = :tags
                WHERE lgp_id = :id',
            );
            $update->bindValue(':content', ...(!\is_null($content) ? [$this->normalizeQuery($content), PDO::PARAM_LOB] : [$content, PDO::PARAM_NULL]));
            $children = [];
            foreach ($this->children as $child) {
                $children[] = (string) $child->id;
            }
            $date = $this->date;
            $tags = [];
            foreach ($this->tags as $tag) {
                $tags[] = (string) $tag->id;
            }
            $update->execute(
                [
                    ':id' => $id,
                    ':author' => (string) $this->author?->id,
                    ':children' => !$children ? '' : \serialize($children),
                    ':date' => !$date ? '' : $date->format('c'),
                    ':description' => $this->normalizeQuery($this->description),
                    ':mtime' => $parakeet->createDateTime()->format('c'),
                    ':name' => $this->normalizeQuery($this->name),
                    ':tags' => !$tags ? '' : \serialize($tags),
                ],
            );
            match ($inTransaction) {true => false, default => $db->commit()};
        } catch (Throwable $e) {
            match ($inTransaction) {true => false, default => $db->rollBack()};
            return false;
        }
        return true;
    }
    protected function readProperty_content(mixed $var): ?string {
        $content = parent::readProperty_content($var);
        if (\is_null($content)) {
            return $content;
        }
        $filename = $this->sanitizeString($var);
        if ('' === $filename || !\file_exists($filename) || \is_dir($filename)) {
            return $content;
        }
        try {
            $this->mtime_content = $this->parakeet->createDateTime(\date('c', \filemtime($filename)));
        } catch (Throwable) {}
        return $content;
    }
}
