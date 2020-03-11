<?php
/**
 * SimpleDI Example
 */

require __DIR__ . '/../SmartDI.php';

class CarDealer {
    public function __construct(Ford $ford, Audi $audi, Porsche $porsche)
    {
        $this->resolveCars($ford, $audi, $porsche);
    }
    
    ...
}

class Ford {};
class Audi {};
class Porsche {};
