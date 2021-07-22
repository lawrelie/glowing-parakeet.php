<?php
namespace Lawrelie\GlowingParakeet\Contents\SearchResults;
use DomainException;
class SearchResult extends \Lawrelie\GlowingParakeet\Contents\Contents {
    public function createChild(mixed $name, ?string $className = self::class): parent {
        $result = parent::createChild(['name' => $name], $className);
        if ($result instanceof self) {
            return $result;
        }
        throw new DomainException;
    }
    protected function readProperty_queryKey(): string {
        return $this->parakeet->queryKeys['search'];
    }
}
