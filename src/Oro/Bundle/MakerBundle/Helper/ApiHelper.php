<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;

/**
 * Provide array suitable for yaml to generate validation rules.
 */
class ApiHelper
{
    public static function getConfiguration(array $configData): array
    {
        $config = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (empty($entityConfig['configuration']['configure_api'])) {
                continue;
            }

            $config[MetadataStorage::getClassName($entityName)] = null;
        }

        if ($config) {
            return ['entities' => $config];
        }

        return [];
    }
}
