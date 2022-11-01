<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide array suitable for yaml to generate search configuration.
 * @SuppressWarnings(PHPMD)
 */
class SearchHelper
{
    private const SIMPLE_SCALAR_TYPES = [
        'string',
        'email'
    ];

    private const ALL_SCALAR_TYPES = [
        'string',
        'email',
        'text',
        'html',
        'wysiwyg'
    ];

    public static function getSearchConfig(array $configData): array
    {
        $searchConfig = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (!CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }
            if (empty($entityConfig['configuration']['configure_search'])) {
                continue;
            }

            $className = MetadataStorage::getClassName($entityName);
            $shortClassName = Str::getShortClassName($className);
            $routes = CrudHelper::getRouteNames($entityName);
            $templatePrefix = LocationMapper::getEntityTemplateTwigPathPrefix($shortClassName);

            $searchConfig[$className] = [
                'alias' => MetadataStorage::getClassMetadata($entityName, 'prefix'),
                'route' => [
                    'name' => $routes['view'],
                    'parameters' => ['id' => 'id']
                ],
                'search_template' => sprintf('%s/searchResult.html.twig', $templatePrefix)
            ];

            $fields = [];

            $iterateFields = array_merge(
                $entityConfig['fields'],
                MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_many', []),
                MetadataStorage::getClassMetadata($entityName, 'inverse_one_to_many', []),
                MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', [])
            );
            foreach ($iterateFields as $fieldName => $fieldConfig) {
                $fieldType = $fieldConfig['type'];
                $name = Str::asLowerCamelCase($fieldName);

                if (!in_array($fieldType, self::ALL_SCALAR_TYPES, true)
                    && $fieldType !== 'enum'
                    && $fieldType !== 'enum[]'
                    && $fieldType !== 'relation'
                ) {
                    continue;
                }

                if ($fieldType === 'relation') {
                    $relationFields = [];
                    if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal')) {
                        $title = Str::asLowerCamelCase(
                            MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'entity_title')
                        );
                        if ($title) {
                            $relationFields[] = [
                                'name' => $title,
                                'target_type' => 'text',
                                'target_fields' => [Str::pluralCamelCaseToSingular($name) . ucfirst($title)]
                            ];
                        }
                    } else {
                        $relationAlias = MetadataStorage::getAlias($fieldConfig['relation_target']);
                        $relationConfig = $configData['entities'][$relationAlias];
                        foreach ($relationConfig['fields'] as $relationFieldName => $relationFieldConfig) {
                            $relationName = Str::asLowerCamelCase($relationFieldName);
                            if (in_array($relationFieldConfig['type'], self::SIMPLE_SCALAR_TYPES, true)) {
                                $relationFields[] = [
                                    'name' => $relationName,
                                    'target_type' => 'text',
                                    'target_fields' => [Str::pluralCamelCaseToSingular($name) . ucfirst($relationName)]
                                ];
                            }
                        }
                    }

                    if (!$relationFields) {
                        continue;
                    }
                    $fields[] = [
                        'name' => $name,
                        'relation_type' => $fieldConfig['relation_type'],
                        'relation_fields' => $relationFields
                    ];

                    continue;
                }

                if ($fieldType === 'enum') {
                    $fields[] = [
                        'name' => $name,
                        'relation_type' => 'many-to-one',
                        'relation_fields' => [
                            [
                                'name' => 'name',
                                'target_type' => 'text',
                                'target_fields' => [$name . 'Name']
                            ]
                        ]
                    ];

                    continue;
                }

                if ($fieldType === 'enum[]') {
                    $fields[] = [
                        'name' => $name,
                        'relation_type' => 'many-to-many',
                        'relation_fields' => [
                            [
                                'name' => 'name',
                                'target_type' => 'text',
                                'target_fields' => [$name . 'Name']
                            ]
                        ]
                    ];

                    continue;
                }

                $fields[] = [
                    'name' => $name,
                    'target_type' => 'text',
                    'target_fields' => [$name]
                ];
            }
            $searchConfig[$className]['fields'] = $fields;
        }

        if ($searchConfig) {
            return ['search' => $searchConfig];
        }

        return [];
    }
}
