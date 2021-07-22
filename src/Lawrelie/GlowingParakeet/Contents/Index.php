<?php
namespace Lawrelie\GlowingParakeet\Contents;
class Index extends Contents {
    public function createAdoptedChild(iterable $properties, string $className = parent::class): parent {
        $child = $this->createChild($properties, $className);
        $directories = $this->parakeet->directories;
        return match ($child->id->fromIndex) {
            $directories['date'] => $this->createDateArchives($properties),
            $directories['search'] => $this->createSearchResults($properties),
            default => $child,
        };
    }
    public function createChild(iterable $properties, ?string $className = Regular::class): parent {
        return parent::createChild($properties, $className);
    }
    public function createDateArchives(iterable $properties, string $className = DateArchives\DateArchives::class): DateArchives\DateArchives {
        return $this->createChild($properties, $className);
    }
    public function createSearchResults(iterable $properties, string $className = SearchResults\SearchResults::class): SearchResults\SearchResults {
        return $this->createChild($properties, $className);
    }
    public function queryAdopted(string $query): ?parent {
        $id = $this->id . Properties\Id::SEPARATOR . $this->id->normalize($query);
        foreach ($this->adoptedChildren as $child) {
            try {
                $relative = $child->id->relativeTo($id);
                return '' === $relative ? $child : $child->query($relative);
            } catch (\DomainException) {}
        }
        return null;
    }
    protected function readProperty_adoptedChildren(mixed $var): array {
        $children = [];
        foreach ($this->sanitizeIterator($var) as $v) {
            try {
                $child = $this->createAdoptedChild($this->readProperties($v));
                $children[(string) $child->id] = $child;
            } catch (\Throwable) {}
        }
        return $this->sortContents($children);
    }
    protected function readProperty_archiveDirectory(): Regular {
        return $this->query($this->parakeet->directories['archives']);
    }
    protected function readProperty_dateArchives(): DateArchives\DateArchives {
        return $this->queryAdopted($this->parakeet->directories['date']);
    }
    protected function readProperty_searchResults(): SearchResults\SearchResults {
        return $this->queryAdopted($this->parakeet->directories['search']);
    }
    protected function readProperty_tagDirectory(): Regular {
        return $this->query($this->parakeet->directories['tags']);
    }
    protected function readProperty_userDirectory(): Regular {
        return $this->query($this->parakeet->directories['users']);
    }
}
