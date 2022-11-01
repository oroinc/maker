<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Prepare metadata for generation of controllers and related templates.
 * Provide some useful methods to get route-related data.
 *
 *  @SuppressWarnings(PHPMD)
 */
class CrudHelper
{
    public static function isCrudEnabled(array $entityConfig): bool
    {
        return !empty($entityConfig['configuration']['create_crud'])
            && empty($entityConfig['configuration']['is_related_entity']);
    }

    public static function getRouteNames(string $entityName): array
    {
        $routePrefix = MetadataStorage::getClassMetadata($entityName, 'prefix');

        return [
            'index' => $routePrefix . '_index',
            'view' => $routePrefix . '_view',
            'create' => $routePrefix . '_create',
            'update' => $routePrefix . '_update'
        ];
    }

    public static function getBundlePrefix(array $configData): string
    {
        return Str::asSnakeCase($configData['options']['organization'])
            . '_' . Str::asSnakeCase($configData['options']['package']);
    }

    public static function getDetachRouteName(
        array $configData,
        string $holderEntity,
        string $targetFieldName
    ): string {
        $prefix = self::getBundlePrefix($configData);
        $holderEntityAlias = MetadataStorage::getAlias($holderEntity);
        $holderEntityAlias = Str::asSnakeCase($holderEntityAlias);

        return $prefix . '_' . $holderEntityAlias . '_' . $targetFieldName . '_detach';
    }

    public static function getViewPageBlocks(array $configData, string $entityName): array
    {
        $viewPageData = ['additional_macros' => []];
        $firstColumnFields = [];
        $secondColumnFieldsFields = [];
        $toManyRelations = [];
        $entityAlias = Str::asSnakeCase($entityName);

        $entityConfig = $configData['entities'][$entityName];
        $fields = array_merge(
            $entityConfig['fields'],
            MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_many', []),
            MetadataStorage::getClassMetadata($entityName, 'inverse_one_to_many', [])
        );
        foreach ($fields as $fieldName => $fieldConfig) {
            $name = Str::asLowerCamelCase($fieldName);
            $label = TranslationHelper::getFieldLabel($entityAlias, $fieldName);
            $fieldExpr = sprintf('entity.%s', $name);

            switch ($fieldConfig['type']) {
                case 'relation':
                    if (str_ends_with($fieldConfig['relation_type'], 'to-many')) {
                        $gridName = MetadataStorage::getFieldMetadata($entityName, $fieldName, 'relation_grid_name');
                        if ($gridName) {
                            $toManyRelations[] = [
                                'field_name' => $fieldName,
                                'label' => $label,
                                'grid_name' => $gridName
                            ];
                        }
                    } elseif (str_ends_with($fieldConfig['relation_type'], 'to-one')) {
                        $target = $fieldConfig['relation_target'];
                        $firstColumnFields[] = [
                            'render_type' => 'entity_link',
                            'name' => $name,
                            'label' => $label,
                            'field_expression' => self::getEntityTitleExpression($fieldExpr),
                            'relation_view_route' => MetadataStorage::getClassMetadata($target, 'route_view')
                        ];
                    }

                    break;

                case 'email':
                    $viewPageData['additional_macros']['Email'] = '@OroEmail/macros.html.twig';
                    $firstColumnFields[] = [
                        'render_type' => 'html',
                        'label' => $label,
                        'field_expression' => sprintf('Email.email_address_simple(%s)', $fieldExpr)
                    ];

                    break;

                case 'date':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%s|oro_format_date', $fieldExpr)
                    ];

                    break;

                case 'datetime':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%s|oro_format_datetime', $fieldExpr)
                    ];

                    break;

                case 'image':
                    $secondColumnFieldsFields[] = [
                        'render_type' => 'image',
                        'label' => $label,
                        'name' => $name
                    ];
                    break;

                case 'float':
                case 'decimal':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%1$s ? %1$s|oro_format_decimal : null', $fieldExpr)
                    ];

                    break;

                case 'boolean':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%s ? "Yes"|trans : "No"|trans', $fieldExpr)
                    ];
                    break;

                case 'html':
                    $secondColumnFieldsFields[] = [
                        'render_type' => 'collapsable_html',
                        'label' => $label,
                        'field_name' => $name,
                        'field_expression' => sprintf('%s|oro_html_sanitize', $fieldExpr)
                    ];
                    break;
                case 'wysiwyg':
                    // skip for now to not introduce XSS
                    // TODO: implement some kind of preview
                    break;

                case 'percent':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%1$s ? %1$s|oro_format_percent : null', $fieldExpr)
                    ];
                    break;

                case 'enum':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf("%s|oro_format_name|default('N/A'|trans)", $fieldExpr)
                    ];
                    break;

                case 'enum[]':
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => sprintf('%1$s ? %1$s|join(", ") : null', $fieldExpr)
                    ];
                    break;

                default:
                    $firstColumnFields[] = [
                        'render_type' => 'scalar',
                        'label' => $label,
                        'field_expression' => $fieldExpr
                    ];
            }
        }

        $extendedFieldsField = ['render_type' => 'extend_fields'];
        if ($secondColumnFieldsFields) {
            $secondColumnFieldsFields[] = $extendedFieldsField;
        } else {
            $firstColumnFields[] = $extendedFieldsField;
        }

        if ($firstColumnFields || $secondColumnFieldsFields) {
            if (!$firstColumnFields) {
                // Move html fields to the left if there are no scalar fields
                $firstColumnFields = $secondColumnFieldsFields;
                $secondColumnFieldsFields = [];
            }
            $viewPageData['data_blocks'][] = [
                'alias' => 'general',
                'title' => TranslationHelper::getSectionLabel($configData, $entityAlias, 'general'),
                'fields' => ['column1' => $firstColumnFields, 'column2' => $secondColumnFieldsFields]
            ];
        }

        if ($toManyRelations) {
            $viewPageData['additional_macros']['dataGrid'] = '@OroDataGrid/macros.html.twig';
            foreach ($toManyRelations as $toManyRelation) {
                $viewPageData['data_blocks'][] = [
                    'alias' => $toManyRelation['field_name'],
                    'title' => $toManyRelation['label'],
                    'fields' => [
                        'column1' => [
                            [
                                'render_type' => 'grid',
                                'grid_name' => $toManyRelation['grid_name']
                            ]
                        ]
                    ]
                ];
            }
        }

        return $viewPageData;
    }

    public static function getUpdatePageBlocks(array $configData, string $entityName)
    {
        $updatePageData = [];
        $firstColumnFields = [];
        $secondColumnFields = [];
        $separateSectionFields = [];
        $entityAlias = Str::asSnakeCase($entityName);

        foreach ($configData['entities'][$entityName]['fields'] as $fieldName => $fieldConfig) {
            $name = Str::asLowerCamelCase($fieldName);

            if ($fieldConfig['type'] === 'relation' && str_ends_with($fieldConfig['relation_type'], '-to-many')) {
                // Skip toMany relations in forms as we do not have component to manage them
                continue;
            }

            if ($fieldConfig['type'] === 'wysiwyg') {
                $separateSectionFields[] = [
                    'label' => TranslationHelper::getFieldLabel($entityAlias, $fieldName),
                    'name' => $name
                ];
            } elseif ($fieldConfig['type'] === 'html') {
                $secondColumnFields[] = ['name' => $name];
            } else {
                $firstColumnFields[] = ['name' => $name];
            }
        }

        if ($firstColumnFields || $secondColumnFields) {
            if (!$firstColumnFields) {
                // Move html fields to the left if there are no scalar fields
                $firstColumnFields = $secondColumnFields;
                $secondColumnFields = [];
            }
            $updatePageData['data_blocks'][] = [
                'title' => TranslationHelper::getSectionLabel($configData, $entityAlias, 'general'),
                'fields' => [
                    'column1' => $firstColumnFields,
                    'column2' => $secondColumnFields
                ]
            ];
        }
        foreach ($separateSectionFields as $sectionField) {
            $updatePageData['data_blocks'][] = [
                'title' => $sectionField['label'],
                'fields' => ['column1' => [['name' => $sectionField['name']]]]
            ];
        }

        return $updatePageData;
    }

    public static function getEntityTitleExpression(
        string $fieldExpr = 'entity'
    ): string {
        return sprintf("%s|oro_format_name|default('N/A'|trans)", $fieldExpr);
    }

    public static function getDetachActions(string $entityName, array $entityConfig, array &$uses): array
    {
        $detachActions = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['relation_type'] !== 'many-to-many') {
                continue;
            }

            $targetClass = $fieldConfig['relation_target'];
            $singularField = Str::asSnakeCase(StrHelper::getInflector()->singularize($fieldName));
            $shortTarget = Str::getShortClassName($targetClass);
            $uses[] = $targetClass;
            if (empty($fieldConfig['is_inverse']) && $fieldConfig['is_owning_side']) {
                $detachActions[] = [
                    'plural_field_name' => $fieldName,
                    'route_prefix' => $singularField,
                    'action_name' => Str::asLowerCamelCase($singularField),
                    'remove_method' => Str::asCamelCase($singularField),
                    'target_entity_class' => $shortTarget
                ];
            } elseif (!$fieldConfig['is_owning_side']) {
                $detachActions[] = [
                    'plural_field_name' => $fieldName,
                    'route_prefix' => $singularField,
                    'action_name' => Str::asLowerCamelCase($fieldName) . Str::asCamelCase($entityName),
                    'remove_method' => Str::asCamelCase($singularField),
                    'target_entity_class' => $shortTarget
                ];
            }
        }

        if ($detachActions) {
            $uses[] = 'Oro\Bundle\SecurityBundle\Annotation\CsrfProtection';
            $uses[] = 'Symfony\Component\HttpFoundation\JsonResponse';
            $uses[] = 'Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter';
        }

        return $detachActions;
    }

    public static function getViewPageButtons(array $configData, string $entityName): array
    {
        $buttons = [];
        foreach ($configData['entities'][$entityName]['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['type'] === 'relation' && str_ends_with($fieldConfig['relation_type'], 'to-many')) {
                if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal', false)) {
                    continue;
                }

                $targetEntityName = MetadataStorage::getAlias($fieldConfig['relation_target']);
                $innerTarget = $configData['entities'][$targetEntityName];
                if (empty($innerTarget['configuration']['is_related_entity'])
                    || empty($innerTarget['configuration']['create_import_export'])
                ) {
                    continue;
                }

                $buttons[$fieldName] = [
                    'type' => 'import_export',
                    'import_alias' => ImportExportHelper::getAlias($configData, $targetEntityName)
                ];
            }
        }

        return $buttons;
    }
}
