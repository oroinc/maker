<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * Resolve entity config based on a given generation configuration.
 * @SuppressWarnings(PHPMD)
 */
class OroEntityConfigHelper
{
    public static function getConfig(string $entityName, array $entityConfig, Generator $generator): array
    {
        $config = [];
        $options = $entityConfig['configuration'];

        self::configureRoutes($entityName, $entityConfig, $config);
        self::configureSelectFormType($generator, $entityName, $entityConfig, $config);
        self::configureGrids($entityName, $config);
        self::configureIcon($config);
        foreach (self::getConfigurators() as $key => $configurator) {
            if (isset($options[$key])) {
                if (!isset($config['defaultValues'])) {
                    $config['defaultValues'] = [];
                }

                $configurator($options[$key], $config);
            }
        }

        return $config;
    }

    protected static function getConfigurators(): \Generator
    {
        yield 'owner' => static function (string $option, array &$config) {
            $config['defaultValues']['ownership'] = array_merge(
                $config['defaultValues']['ownership'] ?? [],
                match ($option) {
                    'user' => [
                        'owner_type' => 'USER',
                        'owner_field_name' => 'owner',
                        'owner_column_name' => 'user_owner_id',
                        'organization_field_name' => 'organization',
                        'organization_column_name' => 'organization_id'
                    ],
                    'business_unit' => [
                        'owner_type' => 'BUSINESS_UNIT',
                        'owner_field_name' => 'owner',
                        'owner_column_name' => 'business_unit_owner_id',
                        'organization_field_name' => 'organization',
                        'organization_column_name' => 'organization_id'
                    ],
                    'organization' => [
                        'owner_type' => 'ORGANIZATION',
                        'owner_field_name' => 'organization',
                        'owner_column_name' => 'organization_id'
                    ],
                    default => [],
                }
            );

            if (!isset($config['defaultValues']['security'])) {
                $config['defaultValues']['security'] = [
                    'type' => 'ACL',
                    'group_name' => '',
                    'category' => ''
                ];
            }
        };

        yield 'frontend_owner' => static function (string $option, array &$config) {
            $config['defaultValues']['ownership'] = array_merge(
                $config['defaultValues']['ownership'] ?? [],
                match ($option) {
                    'customer_user' => [
                        'frontend_owner_type' => 'FRONTEND_USER',
                        'frontend_owner_field_name' => 'customerUser',
                        'frontend_owner_column_name' => 'customer_user_id',
                        'frontend_customer_field_name' => 'customer',
                        'frontend_customer_column_name' => 'customer_id'
                    ],
                    'customer' => [
                        'frontend_owner_type' => 'FRONTEND_CUSTOMER',
                        'frontend_owner_field_name' => 'customer',
                        'frontend_owner_column_name' => 'customer_id',
                    ],
                    default => [],
                }
            );
            $config['defaultValues']['security'] = array_merge(
                $config['defaultValues']['security'] ?? [],
                [
                    'type' => 'ACL',
                    'group_name' => 'commerce',
                    'category' => ''
                ]
            );
        };

        yield 'auditable' => static function (bool $option, array &$config) {
            if ($option) {
                $config['defaultValues']['dataaudit'] = ['auditable' => true];
            }
        };
    }

    protected static function configureRoutes(
        string $entityName,
        array $entityConfig,
        array &$config
    ): void {
        $routeNames = CrudHelper::getRouteNames($entityName);
        MetadataStorage::addClassMetadata($entityName, 'route_index', $routeNames['index']);
        MetadataStorage::addClassMetadata($entityName, 'route_view', $routeNames['view']);
        MetadataStorage::addClassMetadata($entityName, 'route_create', $routeNames['create']);
        MetadataStorage::addClassMetadata($entityName, 'route_update', $routeNames['update']);

        if (!CrudHelper::isCrudEnabled($entityConfig)) {
            return;
        }
        $config['routeName'] = $routeNames['index'];
        $config['routeView'] = $routeNames['view'];
        $config['routeCreate'] = $routeNames['create'];
        $config['routeUpdate'] = $routeNames['update'];
    }

    protected static function configureSelectFormType(
        Generator $generator,
        string $entityName,
        array $entityConfig,
        array &$config
    ): void {
        if (CrudHelper::isCrudEnabled($entityConfig)) {
            FormHelper::configureCreateOrSelectFormTypeClassDetails($generator, $entityName);
            /** @var ClassNameDetails $formTypeClassDetails */
            $formTypeClassDetails = MetadataStorage::getClassMetadata(
                $entityName,
                'select_form_type_class_name_details'
            );
            if ($formTypeClassDetails) {
                $config['defaultValues']['form'] = [
                    'form_type' => $formTypeClassDetails->getFullName(),
                    'grid_name' => GridHelper::getSelectGridName($entityName)
                ];
            }
        }
    }

    private static function configureGrids(string $entityName, array &$config): void
    {
        $config['defaultValues']['grid'] = ['default' => GridHelper::getSelectGridName($entityName)];
    }

    private static function configureIcon(array &$config): void
    {
        $config['defaultValues']['entity'] = ['icon' => 'fa-question'];
    }
}
