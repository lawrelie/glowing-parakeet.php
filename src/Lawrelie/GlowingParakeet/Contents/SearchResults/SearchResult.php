<?php
namespace Lawrelie\GlowingParakeet\Contents\SearchResults;
class SearchResult extends \Lawrelie\GlowingParakeet\Contents\Contents {
    protected function readProperty_queryKey(): string {
        return $this->parakeet->queryKeys['search'];
    }
}
