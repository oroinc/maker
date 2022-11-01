<?php

namespace Oro\Bundle\MakerBundle\Util;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Map file locations to support coexistence of bundle-less and bundle aware directory structures.
 */
class LocationMapper
{
    private static array $configMapping = [
        'acls.yml' => 'oro/acls/%s.yml',
        'actions.yml' => 'oro/actions/%s.yml',
        'api.yml' => 'oro/api/%s.yml',
        'datagrids.yml' => 'oro/datagrids/%s.yml',
        'navigation.yml' => 'oro/navigation/%s.yml',
        'search.yml' => 'oro/search/%s.yml',
        'batch_jobs.yml' => 'batch_jobs/%s.yml',
        'validation.yml' => 'validator/%s.yml'
    ];
    private static string $packageName;

    public static function setPackageName(string $packageName): void
    {
        self::$packageName = $packageName;
    }

    public static function getOroConfigPath(string $srcPath, string $configFileName): string
    {
        return self::getResolvedConfigPath($srcPath, 'Resources/config/oro', $configFileName);
    }

    public static function getConfigPath(string $srcPath, string $configFileName): string
    {
        return self::getResolvedConfigPath($srcPath, 'Resources/config', $configFileName);
    }

    public static function getTemplatePath(string $srcPath, $templateSubPath): string
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $templatesPath = dirname($srcPath) . '/templates';
        } else {
            $templatesPath = $srcPath . '/Resources/views';
        }

        return $templatesPath . '/' . $templateSubPath;
    }

    public static function getEntityTemplateTwigPathPrefix(string $shortClassName): string
    {
        if (!MetadataStorage::getGlobalMetadata('bundle_less')) {
            return self::getTemplateTwigPathPrefix() . $shortClassName;
        }

        return Str::asSnakeCase($shortClassName);
    }

    public static function getTemplateTwigPathPrefix(): string
    {
        if (!MetadataStorage::getGlobalMetadata('bundle_less')) {
            $bundleClass = MetadataStorage::getGlobalMetadata('bundle_class_name');

            return '@' . str_replace('Bundle', '', Str::getShortClassName($bundleClass)) . '/';
        }

        return '';
    }

    public static function getEntityTemplatePath(string $srcPath, string $shortClassName, string $template): string
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $shortClassName = Str::asSnakeCase($shortClassName);
        }

        return self::getTemplatePath($srcPath, $shortClassName . '/' . $template);
    }

    public static function getServicesConfigPath(string $srcPath, string $configFileName): string
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $configFolder = self::getConfigFolder($srcPath);

            return implode('/', [$configFolder, 'services', self::$packageName, $configFileName]);
        }

        return self::getConfigPath($srcPath, $configFileName);
    }

    public static function getTranslationsPath(string $srcPath, string $fileName): string
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $translationsFolder = dirname($srcPath) . '/translations';
        } else {
            $translationsFolder = $srcPath . '/Resources/translations';
        }

        return $translationsFolder . '/' . $fileName;
    }

    private static function getResolvedConfigPath(string $srcPath, string $subFolder, string $fileName): string
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $configFolder = self::getConfigFolder($srcPath);
            $mappedFileName = self::$configMapping[$fileName] ?? $fileName;
            if (str_contains($mappedFileName, '%s')) {
                $mappedFileName = sprintf($mappedFileName, self::$packageName);
            }

            return $configFolder . '/' . $mappedFileName;
        }

        return implode('/', [$srcPath, $subFolder, $fileName]);
    }

    private static function getConfigFolder(string $srcPath): string
    {
        return dirname($srcPath) . '/config';
    }
}
