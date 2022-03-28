<?php

namespace CarstenWalther\DynDNS\Domain\Model;

use CarstenWalther\DynDNS\Utility\ArrayUtility;
use ReflectionException;

/**
 * Class AbstractModel
 */
abstract class AbstractModel
{
    /**
     * @return array
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        return ArrayUtility::objectToArray($this);
    }
}
