<?php
namespace Lawrelie\GlowingParakeet\Contents\SearchResults;
use DomainException, Throwable;
class SearchResults extends \Lawrelie\GlowingParakeet\Contents\Contents {
    private array $children = [];
    public function __get(string $name): mixed {
        return 'children' === $name ? $this->$name : parent::__get($name);
    }
    public function createChild(mixed $name, ?string $className = SearchResult::class): parent {
        $result = parent::createChild(['name' => $name], $className);
        if ($result instanceof SearchResult) {
            return $result;
        }
        throw new DomainException;
    }
    public function query(string $query): ?parent {
        try {
            return $this->children[$query];
        } catch (Throwable) {}
        try {
            $child = $this->createChild($query);
        } catch (Throwable) {
            return null;
        }
        $this->children[$child->name] = $child;
        return $child;
    }
}
