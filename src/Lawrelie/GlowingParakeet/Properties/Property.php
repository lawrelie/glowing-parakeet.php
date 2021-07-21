<?php
namespace Lawrelie\GlowingParakeet\Properties;
use Lawrelie\GlowingParakeet as lgp;
class Property {
    use lgp\ReadProperties;
    public function __construct(iterable $properties, private lgp\Parakeet $parakeet) {
        $this->setReadableProperties($properties);
    }
    protected function readProperty_parakeet(): lgp\Parakeet {
        return $this->parakeet;
    }
}
