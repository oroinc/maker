<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Useful functions for import-export configuration.
 */
class ImportExportHelper
{
    public static function getAlias(array $configData, string $entityName): ?string
    {
        if (!self::isImportExportEnabled($configData['entities'][$entityName])) {
            return null;
        }

        return MetadataStorage::getClassMetadata($entityName, 'prefix');
    }

    public static function isImportExportEnabled(array $entityConfig): bool
    {
        return !empty($entityConfig['configuration']['create_import_export']);
    }

    public static function getIdentityFields(array $entityConfig, string $entityName): array
    {
        $identity = [];
        if (self::isImportExportEnabled($entityConfig)) {
            $identity[] = Str::asLowerCamelCase(MetadataStorage::getClassMetadata($entityName, 'entity_title'));
        }

        return $identity;
    }

    public static function getExportJobName(array $configData, string $entityName): ?string
    {
        $exportJobName = null;
        $entityConfig = $configData['entities'][$entityName] ?? [];
        if (!empty($entityConfig['configuration']['is_related_entity'])) {
            $alias = self::getAlias($configData, $entityName);
            $exportJobName = $alias . '_export_to_csv';
        }

        return $exportJobName;
    }
}
