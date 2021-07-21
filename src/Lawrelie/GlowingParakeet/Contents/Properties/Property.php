<?php
namespace Lawrelie\GlowingParakeet\Contents\Properties;
use Lawrelie\GlowingParakeet as lgp;
class Property extends lgp\Properties\Property {
    public function __construct(iterable $properties, private lgp\Contents\Contents $contents) {
        parent::__construct($properties, $this->contents->parakeet);
    }
    protected function readProperty_contents(): lgp\Contents\Contents {
        return $this->contents;
    }
}
