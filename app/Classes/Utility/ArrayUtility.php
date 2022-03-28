<?php

namespace CarstenWalther\DynDNS\Utility;

use ReflectionClass;
use ReflectionException;

/**
 * ArrayUtility
 */
class ArrayUtility
{
    /**
     * @param $object
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function objectToArray($object)
    {
        if (is_object($object)) {
            $object = self::dismount($object);
        }

        if (is_array($object)) {
            $new = [];
            foreach ($object as $key => $value) {
                $new[$key] = self::objectToArray($value);
            }
        } else {
            $new[] = $object;
        }

        return self::sanitize($new);
    }

    /**
     * @param $object
     *
     * @return array
     */
    public static function dismount($object): array
    {
        $reflectionClass = new ReflectionClass(get_class($object));

        $array = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($object);
            $property->setAccessible(false);
        }

        return $array;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function sanitize($value)
    {
        if (is_array($value) && count($value) <= 1) {
            return end($value);
        }

        return $value;
    }
}
