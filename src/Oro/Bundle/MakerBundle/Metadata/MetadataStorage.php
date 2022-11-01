<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Symfony\Bundle\MakerBundle\Str;

/**
 * Store global,class and field metadata.
 * Call metadata loaders on absence of class or field metadata.
 */
class MetadataStorage
{
    private static array $loaders = [];
    private static array $globalMetadata = [];
    private static array $classMetadata = [];
    private static array $fieldMetadata = [];
    private static array $aliasToClassMap = [];
    private static array $classToAliasMap = [];
    private static array $classAliasCache = [];

    public static function registerMetadataLoader(MetadataLoaderInterface $metadataLoader): void
    {
        self::$loaders[] = $metadataLoader;
    }

    public static function registerClassAlias(string $alias, string $className): void
    {
        self::$aliasToClassMap[$alias] = $className;
        self::$classToAliasMap[$className] = $alias;
    }

    public static function getGlobalMetadata(string $key, mixed $defaultValue = null): mixed
    {
        return self::$globalMetadata[$key] ?? $defaultValue;
    }

    public static function setGlobalMetadata(string $key, mixed $value): void
    {
        self::$globalMetadata[$key] = $value;
    }

    public static function getClassNameByAlias(string $alias): ?string
    {
        return self::$aliasToClassMap[$alias] ?? null;
    }

    public static function getClassName(string $classOrAlias): string
    {
        return self::$aliasToClassMap[$classOrAlias] ?? $classOrAlias;
    }

    public static function addClassMetadata(string $entityClass, string $key, mixed $value): void
    {
        $entityClass = self::getClassName($entityClass);
        self::$classMetadata[$entityClass][$key] = $value;
    }

    public static function appendArrayClassMetadata(
        string $entityClass,
        string $key,
        mixed $value,
        string $valueKey = null
    ): void {
        $storedValue = self::getClassMetadata($entityClass, $key, []);
        if ($valueKey) {
            $storedValue[$valueKey] = $value;
        } else {
            $storedValue[] = $value;
        }
        self::addClassMetadata($entityClass, $key, $storedValue);
    }

    public static function getClassMetadata(string $entityClass, string $key, mixed $default = null): mixed
    {
        $entityClass = self::getClassName($entityClass);
        if (!isset(self::$classMetadata[$entityClass][$key])) {
            foreach (self::$loaders as $loader) {
                if (!$loader instanceof ClassMetadataLoaderInterface) {
                    continue;
                }
                $value = $loader->getClassMetadataValue($entityClass, $key);
                if (null !== $value) {
                    self::addClassMetadata($entityClass, $key, $value);
                }
            }
        }

        return self::$classMetadata[$entityClass][$key] ?? $default;
    }

    public static function addFieldMetadata(string $entityClass, string $fieldName, string $key, mixed $value): void
    {
        $entityClass = self::getClassName($entityClass);
        self::$fieldMetadata[$entityClass][$fieldName][$key] = $value;
    }

    public static function getFieldMetadata(
        string $entityClass,
        string $fieldName,
        string $key,
        mixed $default = null
    ): mixed {
        $entityClass = self::getClassName($entityClass);
        if (!isset(self::$fieldMetadata[$entityClass][$fieldName][$key])) {
            foreach (self::$loaders as $loader) {
                if (!$loader instanceof FieldMetadataLoaderInterface) {
                    continue;
                }
                $value = $loader->getFieldMetadataValue($entityClass, $fieldName, $key);
                if (null !== $value) {
                    self::addFieldMetadata($entityClass, $fieldName, $key, $value);
                }
            }
        }

        return self::$fieldMetadata[$entityClass][$fieldName][$key] ?? $default;
    }

    public static function getAlias(string $aliasOrClass): ?string
    {
        if (array_key_exists($aliasOrClass, self::$aliasToClassMap)) {
            return $aliasOrClass;
        }
        if (array_key_exists($aliasOrClass, self::$classToAliasMap)) {
            return self::$classToAliasMap[$aliasOrClass];
        }

        if (array_key_exists($aliasOrClass, self::$classAliasCache)) {
            return self::$classAliasCache[$aliasOrClass];
        }

        if (preg_match('/(\w+)\\\\Bundle\\\\\w+Bundle\\\\Entity\\\\(\w+)/', $aliasOrClass, $matches)) {
            $alias = Str::asSnakeCase($matches[1]) . '_' . Str::asSnakeCase($matches[2]);
            self::$classAliasCache[$aliasOrClass] = $alias;

            return $alias;
        }

        return null;
    }

    public static function appendGlobalMetadata(string $key, mixed $value, string $valueKey = null): void
    {
        $storedValue = self::getGlobalMetadata($key, []);
        if ($valueKey) {
            $storedValue[$valueKey] = $value;
        } else {
            $storedValue[] = $value;
        }
        self::setGlobalMetadata($key, $storedValue);
    }
}
