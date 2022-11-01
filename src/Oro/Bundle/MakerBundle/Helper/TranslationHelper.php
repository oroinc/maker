<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide array suitable for yaml to generate translation messages.
 */
class TranslationHelper
{
    public static function getTranslationStrings(array $configData): array
    {
        [$org, $package] = self::getPackageData($configData['options']);

        $translations = [$org => [$package => []]];
        foreach ($configData['entities'] as $entityAlias => $entityConfig) {
            $entity = StrHelper::getEntityName($entityAlias);
            $entityLabel = $entityConfig['label'] ?? StrHelper::getUcwords($entityAlias);
            $pluralEntityLabel = StrHelper::getInflector()->pluralize($entityLabel);
            $pluralAlias = StrHelper::getInflector()->pluralize($entityAlias);

            $translations[$org]['organization_label'] = ucwords(
                str_replace('_', ' ', Str::asSnakeCase($configData['options']['organization']))
            );
            $translations[$org][$package]['package_label'] = ucwords(
                str_replace('_', ' ', Str::asSnakeCase($configData['options']['package']))
            );
            $translations[$org][$package][$entity] = [
                'entity_label' => $entityLabel,
                'entity_plural_label' => $pluralEntityLabel,
                'entity_grid_all_view_label' => 'All %entity_plural_label%',
                'id.label' => 'ID'
            ];

            if (CrudHelper::isCrudEnabled($entityConfig)) {
                $translations[$org][$package]['menu'][$pluralAlias] = $pluralEntityLabel;
                $translations[$org][$package]['shortcut']['new_' . $entityAlias . '.label'] =
                    'Create new ' . $entityLabel;
                $translations[$org][$package]['shortcut']['list_' . $pluralAlias . '.label'] =
                    'Show ' . $pluralEntityLabel;
            }
            if (!empty($entityConfig['configuration']['is_related_entity'])) {
                $translations[$org][$package]['actions'][$entityAlias]['create.label'] = 'Add ' . $entityLabel;
                $translations[$org][$package]['actions'][$entityAlias]['update.label'] = 'Edit ' . $entityLabel;
            }

            $translations[$org][$package]['section'][$entityAlias]['general.label'] = 'General';
            $translations[$org][$package]['controller'][$entityAlias]['saved.message'] =
                $entityLabel . ' has been saved';

            $fields = array_merge(
                $entityConfig['fields'],
                MetadataStorage::getClassMetadata($entityAlias, 'inverse_many_to_one', []),
                MetadataStorage::getClassMetadata($entityAlias, 'inverse_one_to_many', []),
                MetadataStorage::getClassMetadata($entityAlias, 'inverse_many_to_many', [])
            );
            foreach ($fields as $fieldName => $fieldConfig) {
                $label = $fieldConfig['label'] ?? StrHelper::getUcwords($fieldName);

                $translations[$org][$package][$entity][Str::asSnakeCase($fieldName) . '.label'] = $label;

                if ($fieldConfig['relation_type'] === 'many-to-many') {
                    $singularField = StrHelper::getInflector()->singularize($fieldName);


                    if (empty($fieldConfig['is_inverse']) && !empty($fieldConfig['is_owning_side'])) {
                        $targetEntity = $entityAlias;
                        $targetField = $singularField;
                    } elseif (empty($fieldConfig['is_owning_side'])) {
                        $targetEntity = MetadataStorage::getAlias($fieldConfig['relation_target']);
                        $targetField = $entityAlias;
                    }
                    $targetFieldName = StrHelper::getUcwords($targetField);
                    $translations[$org][$package]['actions'][$targetEntity][$targetField]
                    ['attach']['label'] = 'Attach ' . $targetFieldName;

                    $translations[$org][$package]['actions'][$targetEntity][$targetField]
                    ['detach']['label'] = 'Detach ' . $targetFieldName;

                    $translations[$org][$package]['actions'][$targetEntity][$targetField]
                    ['attach']['messages']['success'] = $targetFieldName . ' attached';
                }
                if ($fieldConfig['relation_type'] === 'one-to-many') {
                    $key = ConfigHelper::getTranslationKey(
                        'entity',
                        'label',
                        $fieldConfig['relation_target'],
                        MetadataStorage::getClassMetadata($entityAlias, 'table_name')
                    );
                    $translations[$key] = $entityLabel;
                }
            }

            if (isset($entityConfig['configuration']['owner'])) {
                switch ($entityConfig['configuration']['owner']) {
                    case 'user':
                    case 'business_unit':
                        $translations[$org][$package][$entity]['owner.label'] = 'Owner';
                        break;
                }
                $translations[$org][$package][$entity]['organization.label'] = 'Organization';
            }

            if (isset($entityConfig['configuration']['frontend_owner'])) {
                if ($entityConfig['configuration']['frontend_owner'] === 'customer_user') {
                    $translations[$org][$package][$entity]['customer_user.label'] = 'Customer User';
                }
                $translations[$org][$package][$entity]['customer.label'] = 'Customer';
            }
        }

        return $translations;
    }

    public static function getFieldLabel(
        string $entityAlias,
        string $fieldName
    ): string {
        return ConfigHelper::getTranslationKey(
            'entity',
            'label',
            MetadataStorage::getClassName($entityAlias),
            $fieldName
        );
    }

    public static function getSaveMessage(
        array $configData,
        string $entityName
    ): string {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf(
            '%s.%s.controller.%s.saved.message',
            $organization,
            $package,
            $entityAlias
        );
    }

    public static function getEntityLabel(string $entityName): string
    {
        return ConfigHelper::getTranslationKey('entity', 'label', MetadataStorage::getClassName($entityName));
    }

    public static function getEntityPluralLabel(string $entityName): string
    {
        return ConfigHelper::getTranslationKey('entity', 'plural_label', MetadataStorage::getClassName($entityName));
    }

    public static function getSectionLabel(array $configData, string $entityName, string $section): string
    {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf('%s.%s.section.%s.%s.label', $organization, $package, $entityAlias, $section);
    }

    public static function getOrganizationLabel(array $configData): string
    {
        return sprintf('%s.organization_label', self::getPackageData($configData['options'])[0]);
    }

    public static function getPackageLabel(array $configData): string
    {
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf('%s.%s.package_label', $organization, $package);
    }

    public static function getMenuCreateShortcutLabel(array $configData, string $entityName): string
    {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf('%s.%s.shortcut.new_%s.label', $organization, $package, $entityAlias);
    }

    public static function getMenuListShortcutLabel(array $configData, string $entityName): string
    {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);
        $pluralAlias = StrHelper::getInflector()->pluralize($entityAlias);

        return sprintf('%s.%s.shortcut.list_%s.label', $organization, $package, $pluralAlias);
    }

    public static function getEntityActionLabel(array $configData, string $entityName, string $action): string
    {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf('%s.%s.actions.%s.%s.label', $organization, $package, $entityAlias, $action);
    }

    public static function getActionLabel(array $configData, string $entityName, string $field, string $action): string
    {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf('%s.%s.actions.%s.%s.%s.label', $organization, $package, $entityAlias, $field, $action);
    }

    public static function getActionMessage(
        array $configData,
        string $entityName,
        string $field,
        string $action,
        string $message
    ): string {
        $entityAlias = MetadataStorage::getAlias($entityName);
        [$organization, $package] = self::getPackageData($configData['options']);

        return sprintf(
            '%s.%s.actions.%s.%s.%s.messages.%s',
            $organization,
            $package,
            $entityAlias,
            $field,
            $action,
            $message
        );
    }

    private static function getPackageData(array $options): array
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            return ['app', 'entity'];
        }

        return [
            str_replace('_', '', Str::asSnakeCase($options['organization'])),
            str_replace('_', '', Str::asSnakeCase($options['package']))
        ];
    }
}
