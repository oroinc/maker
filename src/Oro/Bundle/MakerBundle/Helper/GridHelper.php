<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide array suitable for yaml to generate grid configs.
 * Contains some useful functions to get grid name for a given entity.
 *
 * @SuppressWarnings(PHPMD)
 */
class GridHelper
{
    private array $addedRelation = [];

    public function __construct(
        private EntityNameResolver $entityNameResolver,
        private ConfigurationProviderInterface $gridConfigProvider
    ) {
    }

    public static function getBaseGridName(string $entityName): string
    {
        $gridName = MetadataStorage::getClassMetadata($entityName, 'grid_name');
        if (!$gridName) {
            $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
            $gridName = str_replace('_', '-', $prefix . '_grid');
            MetadataStorage::addClassMetadata($entityName, 'grid_name', $gridName);
        }

        return $gridName;
    }

    public static function getSelectGridName(string $entityName): string
    {
        return self::getBaseGridName($entityName) . '-select';
    }

    public function getGridsConfiguration(array $configData): array
    {
        $grids = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $entityAlias = Str::asSnakeCase($entityName);
            $entityClass = MetadataStorage::getClassName($entityName);
            $routes = CrudHelper::getRouteNames($entityName);

            $gridName = self::getBaseGridName($entityName);
            $grids[$gridName . '-base'] = [
                'extended_entity_name' => $entityClass,
                'acl_resource' => $routes['view'],
                'options' => [
                    'entityHint' => TranslationHelper::getEntityPluralLabel($entityName)
                ],
                'properties' => [
                    'id' => null
                ],
                'source' => [
                    'type' => 'orm',
                    'query' => [
                        'select' => [
                            'e.id'
                        ],
                        'from' => [
                            ['table' => $entityClass, 'alias' => 'e']
                        ]
                    ]
                ],
            ];
            if (!$this->configureSource($grids[$gridName . '-base'], $entityConfig)) {
                unset($grids[$gridName . '-base']);
                continue;
            }

            $grids[$gridName . '-base']['columns'] = $this->getColumns($entityConfig, $entityAlias);
            $grids[$gridName . '-base']['sorters'] = ['columns' => $this->getSorters($entityConfig)];
            $grids[$gridName . '-base']['filters'] = ['columns' => $this->getFilters($entityName, $entityConfig)];
            $this->addOwnershipFields($grids[$gridName . '-base'], $configData, $entityName);

            $grids[$gridName . '-select'] = [
                'extends' => $gridName . '-base'
            ];

            if (CrudHelper::isCrudEnabled($entityConfig)) {
                $grids[$gridName . '-base-with-view-link'] = [
                    'extends' => $gridName . '-base',
                    'properties' => [
                        'view_link' => [
                            'type' => 'url',
                            'route' => $routes['view'],
                            'params' => ['id']
                        ]
                    ],
                    'actions' => [
                        'view' => [
                            'type' => 'navigate',
                            'label' => 'oro.grid.action.view',
                            'link' => 'view_link',
                            'icon' => 'eye',
                            'acl_resource' => $routes['view'],
                            'rowAction' => true
                        ]
                    ]
                ];
            }

            $baseGrid = CrudHelper::isCrudEnabled($entityConfig)
                ? $gridName . '-base-with-view-link'
                : $gridName . '-base';
            $this->addManyToOneRelationGrids($entityName, $entityConfig, $gridName, $grids, $baseGrid);
            $this->addManyToManyRelationGrids($entityName, $entityConfig, $gridName, $grids, $baseGrid, $configData);

            if (!CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }

            $grids[$gridName] = [
                'extends' => $gridName . '-base-with-view-link',
                'options' => [
                    'entity_pagination' => true
                ]
            ];

            $this->addDateFields($grids[$gridName]);
        }

        return ['datagrids' => $grids];
    }

    protected function addManyToOneRelationGrids(
        string $entityName,
        array $entityConfig,
        string $gridName,
        array &$grids,
        string $baseGrid
    ): void {
        $inverseRelations = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', []);
        $relations = array_merge($entityConfig['fields'], $inverseRelations);
        foreach ($relations as $fieldName => $fieldConfig) {
            $relationType = $fieldConfig['relation_type'] ?? null;
            if ($relationType === 'many-to-one') {
                $field = str_replace('_', '-', Str::asSnakeCase($fieldName));
                $relationGridName = $gridName . '-by-' . $field;
                $this->saveRelationGridName($fieldConfig, $fieldName, $entityName, $relationGridName);
                $grids[$relationGridName] = [
                    'extends' => $baseGrid,
                    'mass_actions' => ['delete' => ['disabled' => true]],
                    'source' => [
                        'query' => [
                            'where' => [
                                'and' => [
                                    sprintf(
                                        'IDENTITY(e.%s) = :holder_entity_id',
                                        Str::asLowerCamelCase($fieldName)
                                    )
                                ]
                            ]
                        ],
                        'bind_parameters' => ['holder_entity_id']
                    ]
                ];
                if (!empty($entityConfig['configuration']['is_related_entity'])) {
                    MetadataStorage::addClassMetadata($entityName, 'relation_grid_name', $relationGridName);
                }
            } elseif ($relationType === 'one-to-many'
                && !MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal')
            ) {
                $field = str_replace('_', '-', Str::asSnakeCase($entityName));
                $relatedEntityGrid = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'grid_name');
                if (!$relatedEntityGrid) {
                    continue;
                }

                $baseGridConfig = $this->gridConfigProvider->getConfiguration($relatedEntityGrid);
                $rootAlias = $baseGridConfig->offsetGetByPath(DatagridConfiguration::FROM_PATH)[0]['alias'];
                $relationGridName = $relatedEntityGrid . '-by-' . $field;
                MetadataStorage::addFieldMetadata(
                    $entityName,
                    $fieldName,
                    'relation_grid_name',
                    $relationGridName
                );
                $grids[$relationGridName] = [
                    'extends' => $relatedEntityGrid,
                    'mass_actions' => ['delete' => ['disabled' => true]],
                    'source' => [
                        'query' => [
                            'where' => [
                                'and' => [
                                    sprintf(
                                        'IDENTITY(%s.%s) = :holder_entity_id',
                                        $rootAlias,
                                        MetadataStorage::getClassMetadata($entityName, 'table_name')
                                    )
                                ]
                            ]
                        ],
                        'bind_parameters' => ['holder_entity_id']
                    ]
                ];
            }
        }
    }

    protected function addManyToManyRelationGrids(
        string $entityName,
        array $entityConfig,
        string $gridName,
        array &$grids,
        string $baseGrid,
        array $configData
    ): void {
        $inverseRelation = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_many', []);
        $relations = array_merge($entityConfig['fields'], $inverseRelation);
        foreach ($relations as $fieldName => $fieldConfig) {
            if (($fieldConfig['relation_type'] ?? '') !== 'many-to-many') {
                continue;
            }

            $field = str_replace('_', '-', Str::asSnakeCase($fieldName));
            $relationGridName = $gridName . '-by-' . $field;
            $this->saveRelationGridName($fieldConfig, $fieldName, $entityName, $relationGridName);

            $grids[$relationGridName] = [
                'extends' => $baseGrid,
                'mass_actions' => ['delete' => ['disabled' => true]],
                'source' => [
                    'query' => [
                        'where' => [
                            'and' => [
                                sprintf(
                                    ':holder_entity_id MEMBER OF e.%s',
                                    Str::asLowerCamelCase($fieldName)
                                )
                            ]
                        ]
                    ],
                    'bind_parameters' => ['holder_entity_id']
                ]
            ];

            if (!empty($fieldConfig['is_inverse']) || !$fieldConfig['is_owning_side']) {
                $relationTarget = $fieldConfig['relation_target'];
                if (!empty($fieldConfig['is_inverse'])) {
                    $singularField = StrHelper::getInflector()->singularize($fieldConfig['inversed_by']);
                    $detachRoute = CrudHelper::getDetachRouteName($configData, $relationTarget, $singularField);
                    $isDirect = true;
                } else {
                    $singularField = $entityName;
                    $singularFieldName = StrHelper::getInflector()->singularize($fieldName);
                    $detachRoute = CrudHelper::getDetachRouteName($configData, $entityName, $singularFieldName);
                    $isDirect = false;
                }

                $this->addDetachAction(
                    $grids,
                    $relationGridName,
                    $singularField,
                    $detachRoute,
                    $configData,
                    $relationTarget,
                    MetadataStorage::getClassMetadata($relationTarget, 'route_update'),
                    $isDirect
                );
            }

            if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal', false)) {
                $relationBaseGridName = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'grid_name');
                if (!$relationBaseGridName) {
                    return;
                }
                $baseGridConfig = $this->gridConfigProvider->getConfiguration($relationBaseGridName);
                $rootAlias = $baseGridConfig->offsetGetByPath(DatagridConfiguration::FROM_PATH)[0]['alias'];

                $relationGridName = $relationBaseGridName
                    . '-by-'
                    . str_replace('_', '-', Str::asSnakeCase(StrHelper::getInflector()->pluralize($entityName)));

                $joinAlias = 'e' . Str::asCamelCase($entityName);
                $grids[$relationGridName] = [
                    'extends' => $relationBaseGridName,
                    'mass_actions' => ['delete' => ['disabled' => true]],
                    'source' => [
                        'query' => [
                            'join' => [
                                'inner' => [
                                    [
                                        'join' => MetadataStorage::getClassName($entityName),
                                        'alias' => $joinAlias,
                                        'conditionType' => 'WITH',
                                        'condition' => sprintf(
                                            '%s MEMBER OF %s.%s',
                                            $rootAlias,
                                            $joinAlias,
                                            Str::asLowerCamelCase($fieldName)
                                        )
                                    ]
                                ]
                            ],
                            'where' => [
                                'and' => [
                                    sprintf('%s = :holder_entity_id', $joinAlias)
                                ]
                            ]
                        ],
                        'bind_parameters' => ['holder_entity_id']
                    ]
                ];
                MetadataStorage::addFieldMetadata(
                    $entityName,
                    $fieldName,
                    'relation_grid_name',
                    $relationGridName
                );

                if ($fieldConfig['is_owning_side']) {
                    $singularField = StrHelper::getInflector()->singularize($fieldName);
                    $this->addDetachAction(
                        $grids,
                        $relationGridName,
                        $singularField,
                        CrudHelper::getDetachRouteName($configData, $entityName, $singularField),
                        $configData,
                        $entityName,
                        MetadataStorage::getClassMetadata($entityName, 'route_update'),
                        true
                    );
                }
            }
        }
    }

    protected function addDateFields(array &$gridConfig): void
    {
        $gridConfig['source']['query']['select'][] = 'e.createdAt';
        $gridConfig['source']['query']['select'][] = 'e.updatedAt';

        $gridConfig['columns']['createdAt'] = [
            'label' => 'oro.ui.created_at',
            'frontend_type' => 'datetime'
        ];
        $gridConfig['columns']['updatedAt'] = [
            'label' => 'oro.ui.updated_at',
            'frontend_type' => 'datetime'
        ];

        $gridConfig['sorters']['columns']['createdAt'] = [
            'data_name' => 'e.createdAt'
        ];
        $gridConfig['sorters']['columns']['updatedAt'] = [
            'data_name' => 'e.updatedAt'
        ];

        $gridConfig['filters']['columns']['createdAt'] = [
            'data_name' => 'e.createdAt',
            'type' => 'datetime'
        ];
        $gridConfig['filters']['columns']['updatedAt'] = [
            'data_name' => 'e.updatedAt',
            'type' => 'datetime'
        ];
    }

    protected function addOwnershipFields(array &$gridConfig, array $configData, string $entityName): void
    {
        $entityConfig = $configData['entities'][$entityName];
        $entityAlias = Str::asSnakeCase($entityName);
        if (isset($entityConfig['configuration']['owner'])) {
            switch ($entityConfig['configuration']['owner']) {
                case 'user':
                    $nameDql = $this->entityNameResolver->getNameDQL(
                        'Oro\Bundle\UserBundle\Entity\User',
                        'ownerUser'
                    );
                    $gridConfig['source']['query']['select'][] = $nameDql . ' as uOwnerName';
                    $gridConfig['source']['query']['join']['left'][] = [
                        'join' => 'e.owner',
                        'alias' => 'ownerUser'
                    ];
                    $gridConfig['columns']['uOwnerName'] = [
                        'label' => TranslationHelper::getFieldLabel($entityAlias, 'owner')
                    ];
                    $gridConfig['sorters']['columns']['uOwnerName'] = [
                        'data_name' => 'uOwnerName'
                    ];
                    $gridConfig['filters']['columns']['uOwnerName'] = [
                        'data_name' => 'uOwnerName',
                        'type' => 'string'
                    ];

                    break;
                case 'business_unit':
                    $gridConfig['source']['query']['select'][] = 'ownerBU.name as buOwnerName';
                    $gridConfig['source']['query']['join']['left'][] = [
                        'join' => 'e.owner',
                        'alias' => 'ownerBU'
                    ];
                    $gridConfig['columns']['buOwnerName'] = [
                        'label' => TranslationHelper::getFieldLabel($entityAlias, 'owner')
                    ];
                    $gridConfig['sorters']['columns']['buOwnerName'] = [
                        'data_name' => 'buOwnerName'
                    ];
                    $gridConfig['filters']['columns']['buOwnerName'] = [
                        'data_name' => 'buOwnerName',
                        'type' => 'string'
                    ];

                    break;
            }
        }

        if (isset($entityConfig['configuration']['frontend_owner'])) {
            if ($entityConfig['configuration']['frontend_owner'] === 'customer_user') {
                $nameDql = $this->entityNameResolver->getNameDQL(
                    'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
                    'ownerCustomerUser'
                );
                $gridConfig['source']['query']['select'][] = $nameDql . ' as cuOwnerName';
                $gridConfig['source']['query']['join']['left'][] = [
                    'join' => 'e.customerUser',
                    'alias' => 'ownerCustomerUser'
                ];
                $gridConfig['columns']['cuOwnerName'] = [
                    'label' => TranslationHelper::getFieldLabel($entityAlias, 'customer_user')
                ];
                $gridConfig['sorters']['columns']['cuOwnerName'] = [
                    'data_name' => 'cuOwnerName'
                ];
                $gridConfig['filters']['columns']['cuOwnerName'] = [
                    'data_name' => 'cuOwnerName',
                    'type' => 'string'
                ];
            }
            $gridConfig['source']['query']['select'][] = 'ownerCustomer.name as cOwnerName';
            $gridConfig['source']['query']['join']['left'][] = [
                'join' => 'e.customer',
                'alias' => 'ownerCustomer'
            ];
            $gridConfig['columns']['cOwnerName'] = [
                'label' => TranslationHelper::getFieldLabel($entityAlias, 'customer')
            ];
            $gridConfig['sorters']['columns']['cOwnerName'] = [
                'data_name' => 'cOwnerName'
            ];
            $gridConfig['filters']['columns']['cOwnerName'] = [
                'data_name' => 'cOwnerName',
                'type' => 'string'
            ];
        }
    }

    protected function configureSource(array &$gridConfig, array $entityConfig): bool
    {
        $hasChanges = false;
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($this->isSkippedField($fieldConfig)) {
                continue;
            }

            $field = Str::asLowerCamelCase($fieldName);
            if (OroEntityHelper::isRelation($fieldConfig)) {
                if ($fieldConfig['type'] === 'enum') {
                    $gridConfig['source']['query']['join']['left'][] = [
                        'join' => 'e.' . Str::asSnakeCase($fieldName),
                        'alias' => $field
                    ];
                    $gridConfig['source']['query']['select'][] = sprintf('%1$s.id as %1$sId', $field);
                    $gridConfig['source']['query']['select'][] = sprintf('%1$s.name as %1$sName', $field);
                    $hasChanges = true;

                    if (!in_array('HINT_TRANSLATABLE', $gridConfig['source']['hints'] ?? [], true)) {
                        $gridConfig['source']['hints'][] = 'HINT_TRANSLATABLE';
                    }
                }
                if ($fieldConfig['relation_type'] === 'many-to-one') {
                    $nameDql = null;
                    if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal')) {
                        $nameDql = $this->entityNameResolver->getNameDQL($fieldConfig['relation_target'], $field);
                    }
                    if ($nameDql) {
                        $nameDql = sprintf('%s as %sTitle', $nameDql, $field);
                    } else {
                        $titleField = MetadataStorage::getClassMetadata(
                            $fieldConfig['relation_target'],
                            'entity_title'
                        );
                        if ($titleField) {
                            $nameDql = sprintf('%s.%s as %1$sTitle', $field, Str::asLowerCamelCase($titleField));
                        }
                    }

                    if ($nameDql) {
                        $this->addedRelation[$fieldConfig['relation_target'] . '::' . $fieldName] = true;
                        $gridConfig['source']['query']['join']['left'][] = [
                            'join' => 'e.' . $field,
                            'alias' => $field
                        ];
                        $gridConfig['source']['query']['select'][] = $nameDql;
                        $hasChanges = true;
                    }
                }
            } else {
                $gridConfig['source']['query']['select'][] = 'e.' . $field;
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }

    protected function getColumns(array $entityConfig, string $entityAlias): array
    {
        $columns = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($this->isSkippedField($fieldConfig)) {
                continue;
            }

            $field = Str::asLowerCamelCase($fieldName);
            if (OroEntityHelper::isRelation($fieldConfig)) {
                if ($fieldConfig['type'] === 'enum') {
                    $columns[$field . 'Name'] = [
                        'label' => TranslationHelper::getFieldLabel($entityAlias, $fieldName)
                    ];
                }
                if ($fieldConfig['relation_type'] === 'many-to-one') {
                    if (empty($this->addedRelation[$fieldConfig['relation_target'] . '::' . $fieldName])) {
                        continue;
                    }

                    $columns[$field . 'Title'] = [
                        'label' => TranslationHelper::getFieldLabel($entityAlias, $fieldName)
                    ];
                }
            } else {
                $columns[$field] = [
                    'label' => TranslationHelper::getFieldLabel($entityAlias, $fieldName)
                ];

                $frontendType = match ($fieldConfig['type']) {
                    'date' => 'date',
                    'datetime' => 'datetime',
                    'boolean' => 'boolean',
                    'integer', 'smallint', 'bigint' => 'integer',
                    'decimal', 'float' => 'decimal',
                    'percent' => 'percent',
                    default => null
                };
                if ($frontendType) {
                    $columns[$field]['frontend_type'] = $frontendType;
                }
            }
        }

        return $columns;
    }

    protected function getSorters(array $entityConfig): array
    {
        $sorters = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($this->isSkippedField($fieldConfig)) {
                continue;
            }

            $field = Str::asLowerCamelCase($fieldName);
            if (OroEntityHelper::isRelation($fieldConfig)) {
                if ($fieldConfig['type'] === 'enum') {
                    $sorters[$field . 'Name'] = [
                        'data_name' => $field . '.name'
                    ];
                }
                if ($fieldConfig['relation_type'] === 'many-to-one') {
                    if (empty($this->addedRelation[$fieldConfig['relation_target'] . '::' . $fieldName])) {
                        continue;
                    }

                    $sorters[$field . 'Title'] = [
                        'data_name' => $field . 'Title'
                    ];
                }
            } else {
                $sorters[$field] = [
                    'data_name' => 'e.' . $field
                ];
            }
        }

        return $sorters;
    }

    protected function getFilters(string $entityName, array $entityConfig): array
    {
        $filters = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($this->isSkippedField($fieldConfig)) {
                continue;
            }

            $field = Str::asLowerCamelCase($fieldName);
            if (OroEntityHelper::isRelation($fieldConfig)) {
                if ($fieldConfig['type'] === 'enum') {
                    $filters[$field . 'Name'] = [
                        'type' => 'enum',
                        'data_name' => $field . '.id',
                        'enum_code' => MetadataStorage::getFieldMetadata($entityName, $fieldName, 'enum_code')
                    ];
                }
                if ($fieldConfig['relation_type'] === 'many-to-one') {
                    $titleField = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'entity_title');
                    if (empty($this->addedRelation[$fieldConfig['relation_target'] . '::' . $fieldName])
                        || !$titleField
                    ) {
                        continue;
                    }

                    $filters[$field . 'Title'] = [
                        'type' => 'entity',
                        'data_name' => $field . '.id',
                        'options' => [
                            'field_type' => 'Symfony\Bridge\Doctrine\Form\Type\EntityType',
                            'field_options' => [
                                'class' => $fieldConfig['relation_target'],
                                'choice_label' => Str::asLowerCamelCase($titleField)
                            ]
                        ]
                    ];
                }
            } else {
                $filters[$field] = [
                    'data_name' => 'e.' . $field
                ];

                $filters[$field]['type'] = match ($fieldConfig['type']) {
                    'date' => 'date',
                    'datetime' => 'datetime',
                    'boolean' => 'boolean',
                    'percent' => 'percent',
                    'integer', 'bigint', 'smallint', 'decimal', 'float' => 'number-range',
                    default => 'string'
                };
            }
        }

        return $filters;
    }

    protected function isSkippedField(array $fieldConfig): bool
    {
        return empty($fieldConfig['force_show_on_grid'])
            && in_array($fieldConfig['type'], ['html', 'wysiwyg', 'text'], true);
    }

    protected function saveRelationGridName(
        mixed $fieldConfig,
        string $fieldName,
        string $entityName,
        string $relationGridName
    ): void {
        if (empty($fieldConfig['inversed_by'])) {
            MetadataStorage::addFieldMetadata(
                $fieldConfig['relation_target'],
                EntityDependencyHelper::getInverseFieldName($entityName, $fieldName),
                'relation_grid_name',
                $relationGridName
            );
        } else {
            MetadataStorage::addFieldMetadata(
                $fieldConfig['relation_target'],
                $fieldConfig['inversed_by'],
                'relation_grid_name',
                $relationGridName
            );
        }
    }

    private function addDetachAction(
        array &$grids,
        string $relationGridName,
        string $singularField,
        string $detachRoute,
        array $configData,
        string $relationTarget,
        string $detachAcl,
        bool $isDirect = true
    ): void {
        $grids[$relationGridName] = array_merge_recursive(
            $grids[$relationGridName],
            [
                'source' => [
                    'query' => [
                        'select' => [
                            '(:holder_entity_id) as holder_entity_id'
                        ]
                    ]
                ],
                'properties' => [
                    'detach_' . $singularField . '_link' => [
                        'type' => 'url',
                        'route' => $detachRoute,
                        'params' => [
                            'holderEntityId' => $isDirect ? 'holder_entity_id' : 'id',
                            'entityId' => $isDirect ? 'id' : 'holder_entity_id'
                        ]
                    ]
                ],
                'actions' => [
                    'detach_' . $singularField => [
                        'type' => 'delete',
                        'label' => TranslationHelper::getActionLabel(
                            $configData,
                            $relationTarget,
                            $singularField,
                            'detach'
                        ),
                        'link' => 'detach_' . $singularField . '_link',
                        'icon' => 'times',
                        'acl_resource' => $detachAcl
                    ]
                ]
            ]
        );
    }
}
