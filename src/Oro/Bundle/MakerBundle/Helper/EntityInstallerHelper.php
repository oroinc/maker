<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * Provide metadata used in template to generate installer migrations.
 */
class EntityInstallerHelper
{
    public function configureTraitsAndInterfaces(array $config, array &$traits, array &$interfaces, &$uses): void
    {
        if ($this->isExtendExtensionRequired($config['entities'])) {
            $interfaces[] = 'Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface';
            $uses[] = 'Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension';
        }

        if ($this->isAttachmentExtensionRequired($config['entities'])) {
            $interfaces[] = 'Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface';
            $traits[] = 'Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait';
        }
    }

    public function isExtendExtensionRequired(array $entities): bool
    {
        foreach ($entities as $entityConfig) {
            foreach ($entityConfig['fields'] as $fieldConfig) {
                if ($fieldConfig['type'] === 'enum' || $fieldConfig['type'] === 'enum[]') {
                    return true;
                }

                if ($fieldConfig['type'] === 'relation' && $fieldConfig['relation_type'] === 'one-to-many') {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAttachmentExtensionRequired(array $entities): bool
    {
        foreach ($entities as $entityConfig) {
            foreach ($entityConfig['fields'] as $fieldConfig) {
                if ($fieldConfig['type'] === 'image') {
                    return true;
                }
            }
        }

        return false;
    }

    public function getTablesConfig(array $entitiesConfig, array &$uses): array
    {
        $tables = [];
        foreach ($entitiesConfig as $entityName => $entityConfig) {
            $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');

            $this->addInstallInstructionsForFields($tables, $uses, $entityName, $entitiesConfig);
            $this->addDateFieldsInstructions($tables, $tableName);
            $this->addOwnerFields($tables, $tableName, $entityConfig);
            $this->addFrontendOwnerFields($tables, $tableName, $entityConfig);

            if (empty($tables[$tableName]['pk'])) {
                $tables[$tableName]['pk']['id'] = [
                    'type' => 'integer',
                    'options' => TplHelper::dumpArray(['autoincrement' => true])
                ];
            }
        }

        return $tables;
    }

    protected function addInstallInstructionsForFields(
        array &$tables,
        array &$uses,
        string $entityName,
        array $entitiesConfig
    ): void {
        $entityConfig = $entitiesConfig[$entityName];
        $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            $installerOptions = OroEntityHelper::getFieldOptions($fieldName, $fieldConfig);

            switch ($fieldConfig['type']) {
                case 'relation':
                    $this->fillRelationConfig(
                        $tables,
                        $fieldName,
                        $entityName,
                        $fieldName,
                        $fieldConfig,
                        $uses
                    );
                    break;
                case 'enum':
                case 'enum[]':
                    $this->fillEnumRelationConfig(
                        $tables,
                        $fieldName,
                        $entityName,
                        $entityConfig,
                        $fieldConfig['type'] !== 'enum'
                    );
                    break;
                case 'image':
                    $this->fillImageRelationConfig(
                        $tables,
                        $fieldName,
                        $entityName
                    );
                    break;

                case 'wysiwyg':
                    $tables[$tableName]['fields'][$fieldName] = [
                        'type' => $installerOptions['type'],
                        'options' => $this->getInstallerFieldOptions($installerOptions)
                    ];
                    $uses[] = 'Oro\Bundle\EntityExtendBundle\Migration\OroOptions';
                    $uses[] = 'Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager';
                    $uses[] = 'Oro\Bundle\EntityConfigBundle\Entity\ConfigModel';
                    $uses[] = 'Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope';
                    $extendFieldOptions = [
                        'notnull' => false,
                        'OroOptions::KEY' => [
                            'ExtendOptionsManager::MODE_OPTION' => 'ConfigModel::MODE_HIDDEN'
                        ]
                    ];
                    $tables[$tableName]['fields'][$fieldName . '_style'] = [
                        'type' => 'wysiwyg_style',
                        'options' => TplHelper::dumpArray($extendFieldOptions)
                    ];
                    $tables[$tableName]['fields'][$fieldName . '_properties'] = [
                        'type' => 'wysiwyg_properties',
                        'options' => TplHelper::dumpArray($extendFieldOptions)
                    ];

                    break;
                default:


                    $tables[$tableName]['fields'][$fieldName] = [
                        'type' => $installerOptions['type'],
                        'options' => $this->getInstallerFieldOptions($installerOptions)
                    ];
            }
        }

        $inverseManyToOne = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', []);
        foreach ($inverseManyToOne as $fieldName => $fieldConfig) {
            $this->fillRelationConfig(
                $tables,
                $fieldName,
                $entityName,
                $fieldName,
                $fieldConfig,
                $uses
            );
        }
    }

    protected function fillRelationConfig(
        array &$tables,
        string $relationName,
        string $entityName,
        string $fieldName,
        array $fieldConfig,
        array &$uses
    ): void {
        switch ($fieldConfig['relation_type']) {
            case 'many-to-many':
                $this->fillManyToManyRelationConfig(
                    $tables,
                    $entityName,
                    $fieldName,
                    $fieldConfig,
                    MetadataStorage::getClassMetadata($entityName, 'table_name')
                );
                break;
            case 'many-to-one':
                $this->fillManyToOneRelationConfig(
                    $tables,
                    $relationName,
                    $entityName,
                    $fieldConfig
                );
                break;

            case 'one-to-many':
                if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal')) {
                    $this->fillOneToManyRelationConfig(
                        $tables,
                        $entityName,
                        $fieldName,
                        $fieldConfig,
                        $uses
                    );
                }
                break;
        }
    }

    protected function fillManyToManyRelationConfig(
        array &$tables,
        string $entityName,
        string $fieldName,
        array $fieldConfig,
        string $tableName
    ): void {
        $targetInfo = $this->getTargetTableInfo($fieldConfig);
        $joinTableName = MetadataStorage::getFieldMetadata($entityName, $fieldName, 'join_table_name');
        $joinColumn = MetadataStorage::getFieldMetadata($entityName, $fieldName, 'join_column');
        $inverseJoinColumn = MetadataStorage::getFieldMetadata($entityName, $fieldName, 'inverse_join_column');
        $tables[$joinTableName]['is_join_table'] = true;

        $tables[$joinTableName]['relations'][$joinColumn] = [
            'target_table_name' => $tableName,
            'target_pk_field_name' => 'id',
            'target_pk_type' => 'integer',
            'on_delete' => 'CASCADE'
        ];
        $tables[$joinTableName]['relations'][$inverseJoinColumn] = [
            'target_table_name' => $targetInfo['table_name'],
            'target_pk_field_name' => $targetInfo['pk_field_name'],
            'target_pk_type' => $targetInfo['pk_type'],
            'on_delete' => 'CASCADE'
        ];

        $tables[$joinTableName]['pk'] = [
            $joinColumn => true,
            $inverseJoinColumn => true
        ];
    }

    protected function fillManyToOneRelationConfig(
        array &$tables,
        string $relationName,
        string $entityName,
        array $fieldConfig
    ): void {
        $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');
        $targetInfo = $this->getTargetTableInfo($fieldConfig);

        $tables[$tableName]['relations'][$relationName . '_id'] = [
            'target_table_name' => $targetInfo['table_name'],
            'target_pk_field_name' => $targetInfo['pk_field_name'],
            'target_pk_type' => $targetInfo['pk_type'],
            'on_delete' => $fieldConfig['required'] ? 'CASCADE' : 'SET NULL',
            'options' => $this->getInstallerFieldOptions(OroEntityHelper::getFieldOptions($relationName, $fieldConfig))
        ];
    }

    protected function fillOneToManyRelationConfig(
        array &$tables,
        string $entityName,
        string $fieldName,
        array $fieldConfig,
        array &$uses
    ): void {
        $uses[] = 'Oro\Bundle\EntityBundle\EntityConfig\DatagridScope';
        $uses[] = 'Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope';

        $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');
        $targetInfo = $this->getTargetTableInfo($fieldConfig);

        $oneToManyOptions = [
            'extend' => [
                'owner' => 'ExtendScope::OWNER_CUSTOM',
                'nullable' => true,
                'on_delete' => 'SET NULL'
            ],
            'datagrid' => ['is_visible' => 'DatagridScope::IS_VISIBLE_TRUE'],
            'view' => ['is_displayable' => true],
            'dataaudit' => ['auditable' => empty($fieldConfig['disable_data_audit'])],
        ];

        /** @var ClassNameDetails $formTypeClassDetails */
        $formTypeClassDetails = MetadataStorage::getClassMetadata(
            $entityName,
            'select_form_type_class_name_details'
        );
        if ($formTypeClassDetails) {
            $oneToManyOptions['form'] = [
                'is_enabled' => true,
                'form_type' => $formTypeClassDetails->getFullName(),
                'form_options' => ['required' => !empty($fieldConfig['required'])]
            ];
        }

        $tables[$tableName]['one_to_many'][$fieldName] = [
            'target_table_name' => $targetInfo['table_name'],
            'title_column' => MetadataStorage::getClassMetadata($entityName, 'entity_title') ?? 'id',
            'options' => TplHelper::dumpArray($oneToManyOptions)
        ];
    }

    protected function fillEnumRelationConfig(
        array &$tables,
        string $fieldName,
        string $entityName,
        array $entityConfig,
        bool $isMultiple = false
    ): void {
        $options = [];
        $fieldConfig = $entityConfig['fields'][$fieldName];
        $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');
        if (empty($fieldConfig['disable_data_audit'])) {
            $options['dataaudit'] = ['auditable' => true];
        }
        if (!empty($fieldConfig['disable_import_export'])) {
            $options['importexport'] = ['excluded' => true];
        }
        $tables[$tableName]['enums'][$fieldName] = [
            'is_multiple' => $isMultiple,
            'enum_code' => MetadataStorage::getFieldMetadata($entityName, $fieldName, 'enum_code'),
            'options' => TplHelper::dumpArray($options)
        ];
    }

    protected function fillImageRelationConfig(
        array &$tables,
        string $fieldName,
        string $entityName
    ): void {
        $tableName = MetadataStorage::getClassMetadata($entityName, 'table_name');
        $tables[$tableName]['images'][$fieldName] = [];
    }

    protected function addDateFieldsInstructions(
        array &$tables,
        string $tableName
    ): void {
        // Exclude create/updated at fields from import-export.
        $excludeImportExportOptions = [
            'OroOptions::KEY' => [
                'importexport' => ['excluded' => true]
            ]
        ];

        $tables[$tableName]['fields']['created_at'] = [
            'type' => 'datetime',
            'options' => TplHelper::dumpArray($excludeImportExportOptions)
        ];
        $tables[$tableName]['fields']['updated_at'] = [
            'type' => 'datetime',
            'options' => TplHelper::dumpArray($excludeImportExportOptions)
        ];
    }

    protected function addOwnerFields(
        array &$tables,
        string $tableName,
        array $entityConfig
    ): void {
        if (isset($entityConfig['configuration']['owner'])) {
            switch ($entityConfig['configuration']['owner']) {
                case 'user':
                    $tables[$tableName]['relations']['user_owner_id'] = [
                        'target_table_name' => 'oro_user',
                        'target_pk_field_name' => 'id',
                        'target_pk_type' => 'integer',
                        'on_delete' => 'SET NULL',
                        'options' => TplHelper::dumpArray(['notnull' => false])
                    ];
                    break;
                case 'business_unit':
                    $tables[$tableName]['relations']['business_unit_owner_id'] = [
                        'target_table_name' => 'oro_business_unit',
                        'target_pk_field_name' => 'id',
                        'target_pk_type' => 'integer',
                        'on_delete' => 'SET NULL',
                        'options' => TplHelper::dumpArray(['notnull' => false])
                    ];
                    break;
            }
            $tables[$tableName]['relations']['organization_id'] = [
                'target_table_name' => 'oro_organization',
                'target_pk_field_name' => 'id',
                'target_pk_type' => 'integer',
                'on_delete' => 'SET NULL',
                'options' => TplHelper::dumpArray(['notnull' => false])
            ];
        }
    }

    protected function addFrontendOwnerFields(
        array &$tables,
        string $tableName,
        array $entityConfig
    ): void {
        if (isset($entityConfig['configuration']['frontend_owner'])) {
            if ($entityConfig['configuration']['frontend_owner'] === 'customer_user') {
                $tables[$tableName]['relations']['customer_user_id'] = [
                    'target_table_name' => 'oro_customer_user',
                    'target_pk_field_name' => 'id',
                    'target_pk_type' => 'integer',
                    'on_delete' => 'SET NULL',
                    'options' => TplHelper::dumpArray(['notnull' => false])
                ];
            }
            $tables[$tableName]['relations']['customer_id'] = [
                'target_table_name' => 'oro_customer',
                'target_pk_field_name' => 'id',
                'target_pk_type' => 'integer',
                'on_delete' => 'SET NULL',
                'options' => TplHelper::dumpArray(['notnull' => false])
            ];
        }
    }

    private function getTargetTableInfo(array $fieldConfig): array
    {
        $tableName = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'table_name');
        $idInfo = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'id_info');

        if (!$idInfo) {
            throw new \RuntimeException(sprintf(
                'Unable to get ID field metadata for entity %s.' .
                ' Please check that installation with this entity was performed and caches are warmed up.',
                $fieldConfig['relation_target']
            ));
        }

        return [
            'table_name' => $tableName,
            'pk_field_name' => $idInfo['field_name'],
            'pk_type' => $idInfo['field_type']
        ];
    }

    private function getInstallerFieldOptions(array $options): ?string
    {
        $installerOptions = [];
        if (!empty($options['nullable'])) {
            $installerOptions['notnull'] = false;
        }
        if (isset($options['length'])) {
            $installerOptions['length'] = $options['length'];
        }
        if (isset($options['options']['default'])) {
            $installerOptions['default'] = $options['options']['default'];
        }

        if ($installerOptions) {
            return TplHelper::dumpArray($installerOptions);
        }

        return null;
    }
}
