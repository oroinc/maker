<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Useful string functions
 */
class StrHelper
{
    private static ?Inflector $inflector = null;

    public static function getInflector(): Inflector
    {
        if (null === static::$inflector) {
            static::$inflector = InflectorFactory::create()->build();
        }

        return static::$inflector;
    }

    public static function getUcwords(string $string): string
    {
        return str_replace('_', ' ', ucwords(Str::asSnakeCase($string), '_'));
    }

    /**
     * @param string $entityName
     * @return array|string|string[]
     */
    public static function getEntityName(string $entityName): string|array
    {
        return str_replace('_', '', $entityName);
    }
}
