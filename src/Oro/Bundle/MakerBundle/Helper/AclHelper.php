<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;

/**
 * Provide array suitable for yaml to generate acls for entities that has no suitable controllers with ACL annotation.
 */
class AclHelper
{
    public static function getAcls(array $configData): array
    {
        $acls = [];

        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }

            // As there is no controller for related entities we should define ACLs for them using config
            $className = MetadataStorage::getClassName($entityName);
            $routes = CrudHelper::getRouteNames($entityName);
            $acls[$routes['view']] = [
                'type' => 'entity',
                'permission' => 'VIEW',
                'bindings' => null,
                'class' => $className
            ];
            $acls[$routes['update']] = [
                'type' => 'entity',
                'permission' => 'EDIT',
                'bindings' => null,
                'class' => $className
            ];

            $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
            $acls[$prefix . '_delete'] = [
                'type' => 'entity',
                'permission' => 'DELETE',
                'bindings' => null,
                'class' => $className
            ];
        }

        return $acls;
    }
}
