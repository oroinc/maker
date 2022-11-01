<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Provide array suitable for yaml to generate actions.
 *
 * - generates attach actions for -to-many relations
 */
class ActionsHelper
{
    public static function getActions(array $configData, Generator $generator, string $srcPath): array
    {
        $actions = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (!empty($entityConfig['configuration']['is_related_entity'])) {
                $className = MetadataStorage::getClassName($entityName);
                $shortClassName = Str::getShortClassName($className);
                $templatePath = LocationMapper::getEntityTemplateTwigPathPrefix($shortClassName)
                    . '/actions/update.html.twig';
                $generator->generateFile(
                    LocationMapper::getEntityTemplatePath($srcPath, $shortClassName, 'actions/update.html.twig'),
                    __DIR__ . '/../Resources/skeleton/crud/templates/actions/update.html.twig.tpl.php',
                    [
                        'fields' => array_map(
                            static fn(string $fieldName) => Str::asLowerCamelCase($fieldName),
                            array_keys($entityConfig['fields'])
                        )
                    ]
                );

                self::addActionsForRelatedEntity(
                    $entityName,
                    $templatePath,
                    $configData,
                    $actions
                );
                continue;
            }

            self::addAttachActionsForToManyRelations(
                $entityConfig,
                $entityName,
                $configData,
                $actions
            );
        }

        return $actions;
    }

    private static function addAttachActionsForToManyRelations(
        array $entityConfig,
        string $entityName,
        array $configData,
        array &$actions
    ): void {
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['relation_type'] !== 'many-to-many') {
                continue;
            }

            if (empty($fieldConfig['is_inverse']) && $fieldConfig['is_owning_side']) {
                self::addOwningSideAttachAction(
                    $actions,
                    $entityName,
                    $fieldName,
                    $fieldConfig['relation_target'],
                    $configData
                );
            } elseif (!$fieldConfig['is_owning_side']) {
                $relationTarget = $fieldConfig['relation_target'];
                $singularField = StrHelper::getInflector()->singularize($fieldName);
                $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
                /** @var TypeGuess|null $selectFormType */
                $selectFormType = MetadataStorage::getClassMetadata(
                    $entityName,
                    'select_form_type'
                );
                $inverseFieldName = EntityDependencyHelper::getInverseFieldName($entityName, $fieldName);
                $actions[$prefix . '_' . $singularField . '_attach'] = [
                    'label' => TranslationHelper::getActionLabel($configData, $relationTarget, $entityName, 'attach'),
                    'routes' => [MetadataStorage::getClassMetadata($relationTarget, 'route_view')],
                    'acl_resource' => [MetadataStorage::getClassMetadata($relationTarget, 'route_update')],
                    'button_options' => ['icon' => 'fa-plus'],
                    'form_options' => [
                        'attribute_fields' => [
                            'entity' => [
                                'form_type' => $selectFormType->getType(),
                                'options' => $selectFormType->getOptions()
                            ]
                        ]
                    ],
                    'form_init' => [
                        ['@assign_value' => ['$.entity', null]]
                    ],
                    'attributes' => [
                        'entity' => [
                            'label' => TranslationHelper::getEntityLabel($entityName),
                            'type' => 'object',
                            'options' => [
                                'class' => MetadataStorage::getClassName($entityName)
                            ]
                        ]
                    ],
                    'actions' => [
                        [
                            '@call_method' => [
                                'object' => '$.entity',
                                'method' => 'add' . Str::asCamelCase($singularField),
                                'method_parameters' => ['$.data']
                            ]
                        ],
                        [
                            '@flush_entity' => '$.entity'
                        ],
                        [
                            '@refresh_grid' => MetadataStorage::getFieldMetadata(
                                $relationTarget,
                                $inverseFieldName,
                                'relation_grid_name'
                            )
                        ],
                        [
                            '@flash_message' => [
                                'message' => TranslationHelper::getActionMessage(
                                    $configData,
                                    $relationTarget,
                                    $entityName,
                                    'attach',
                                    'success'
                                ),
                                'type' => 'success'
                            ]
                        ]
                    ]
                ];
            }
        }
    }

    private static function addActionsForRelatedEntity(
        string $entityName,
        string $templatePath,
        array $configData,
        array &$actions
    ): void {
        $entityClass = MetadataStorage::getClassName($entityName);
        $entityAlias = Str::asSnakeCase($entityName);
        $routes = CrudHelper::getRouteNames($entityAlias);
        $gridName = MetadataStorage::getClassMetadata($entityName, 'relation_grid_name');
        $inverseRelation = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', []);
        $holderEntityField = array_keys($inverseRelation)[0];
        $holderEntityName = $inverseRelation[$holderEntityField]['relation_target'];
        $holderRoutes = CrudHelper::getRouteNames($holderEntityName);

        $manageActionTemplate = [
            'applications' => ['default'],
            'frontend_options' => [
                'template' => $templatePath,
                'options' => [
                    'allowMaximize' => true,
                    'allowMinimize' => true,
                    'dblclick' => 'maximize',
                    'maximizedHeightDecreaseBy' => 'minimize-bar',
                    'width' => 650
                ]
            ],
            'attributes' => [
                'entity' => [
                    'label' => ' ',
                    'type' => 'object',
                    'options' => ['class' => $entityClass]
                ]
            ],
            'form_options' => [
                'attribute_fields' => [
                    'entity' => [
                        'form_type' => MetadataStorage::getClassMetadata($entityName, 'form_type')
                    ]
                ]
            ],
            'actions' => [
                ['@refresh_grid' => $gridName],
                [
                    '@flash_message' => [
                        'message' => TranslationHelper::getSaveMessage($configData, $entityAlias),
                        'type' => 'success'
                    ]
                ]
            ]
        ];

        $actions[$routes['create']] = array_merge_recursive([
            'label' => TranslationHelper::getEntityActionLabel($configData, $entityAlias, 'create'),
            'routes' => [$holderRoutes['view']],
            'acl_resource' => ['CREATE', 'entity:' . $entityClass],
            'button_options' => ['icon' => 'fa-plus'],
            'form_options' => [
                'attribute_default_values' => ['entity' => '$.entity']
            ],
            'form_init' => [
                [
                    '@create_object' => [
                        'class' => $entityClass,
                        'attribute' => '$.entity'
                    ]
                ],
                [
                    '@assign_value' => ['$.entity.' . $holderEntityField, '$.data']
                ]
            ],
            'actions' => [
                ['@flush_entity' => '$.entity']
            ]
        ], $manageActionTemplate);

        $actions[$routes['update']] = array_merge_recursive([
            'label' => TranslationHelper::getEntityActionLabel($configData, $entityAlias, 'update'),
            'datagrids' => [$gridName],
            'acl_resource' => [$routes['update']],
            'button_options' => ['icon' => 'fa-pencil'],
            'actions' => [
                ['@flush_entity' => '$.data']
            ]
        ], $manageActionTemplate);

        $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
        $actions[$prefix . '_delete'] = [
            'extends' => 'DELETE',
            'for_all_entities' => false,
            'for_all_datagrids' => false,
            'replace' => ['datagrids', 'preconditions', 'exclude_datagrids'],
            'datagrids' => [$gridName],
            'preconditions' => ['@not_blank' => '$.data']
        ];
    }

    private static function addOwningSideAttachAction(
        array &$actions,
        string $entityName,
        string $fieldName,
        string $relationTarget,
        array $configData
    ): void {
        $singularField = StrHelper::getInflector()->singularize($fieldName);
        $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
        /** @var TypeGuess|null $selectFormType */
        $selectFormType = MetadataStorage::getClassMetadata(
            $relationTarget,
            'select_form_type'
        );
        if (!$selectFormType) {
            return;
        }

        $actions[$prefix . '_' . $singularField . '_attach'] = [
            'label' => TranslationHelper::getActionLabel($configData, $entityName, $singularField, 'attach'),
            'routes' => [MetadataStorage::getClassMetadata($entityName, 'route_view')],
            'acl_resource' => [MetadataStorage::getClassMetadata($entityName, 'route_update')],
            'button_options' => ['icon' => 'fa-plus'],
            'form_options' => [
                'attribute_fields' => [
                    'entity' => [
                        'form_type' => $selectFormType->getType(),
                        'options' => $selectFormType->getOptions()
                    ]
                ]
            ],
            'form_init' => [
                ['@assign_value' => ['$.entity', null]]
            ],
            'attributes' => [
                'entity' => [
                    'label' => TranslationHelper::getEntityLabel($relationTarget),
                    'type' => 'object',
                    'options' => [
                        'class' => $relationTarget
                    ]
                ]
            ],
            'actions' => [
                [
                    '@call_method' => [
                        'object' => '$.data',
                        'method' => 'add' . Str::asCamelCase($singularField),
                        'method_parameters' => ['$.entity']
                    ]
                ],
                [
                    '@flush_entity' => '$.data'
                ],
                [
                    '@refresh_grid' => MetadataStorage::getFieldMetadata(
                        $entityName,
                        $fieldName,
                        'relation_grid_name'
                    )
                ],
                [
                    '@flash_message' => [
                        'message' => TranslationHelper::getActionMessage(
                            $configData,
                            $entityName,
                            $singularField,
                            'attach',
                            'success'
                        ),
                        'type' => 'success'
                    ]
                ]
            ]
        ];
    }
}
