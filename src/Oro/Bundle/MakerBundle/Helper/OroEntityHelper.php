<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Renderer\AnnotationRenderer;
use Oro\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;

/**
 * Helper to manipulate entity structure.
 * Uses ClassSourceManipulator
 *
 * @SuppressWarnings(PHPMD)
 */
class OroEntityHelper
{
    private const RELATION_TYPE_MAP = [
        'one-to-many' => EntityRelation::ONE_TO_MANY,
        'many-to-many' => EntityRelation::MANY_TO_MANY,
        'many-to-one' => EntityRelation::MANY_TO_ONE
    ];

    private FileManager $fileManager;
    private AnnotationRenderer $annotationRenderer;

    public function __construct(
        AnnotationRenderer $phpDocRenderer,
        FileManager $fileManager
    ) {
        $this->annotationRenderer = $phpDocRenderer;
        $this->fileManager = $fileManager;
    }

    public function setFileManager(FileManager $fileManager): void
    {
        $this->fileManager = $fileManager;
    }

    public function fillEntityFields(array $entitiesConfig, string $organization, string $package): void
    {
        $fileManagerOperations = [];
        foreach ($entitiesConfig as $entityName => $entityConfig) {
            $identityFields = ImportExportHelper::getIdentityFields($entityConfig, $entityName);
            $entityPath = MetadataStorage::getClassMetadata($entityName, 'entity_class_path');
            $isRelatedEntity = !empty($entityConfig['configuration']['is_related_entity']);
            if (isset($fileManagerOperations[$entityPath])) {
                $manipulator = $fileManagerOperations[$entityPath];
            } else {
                $manipulator = $this->createClassManipulator($entityPath);
                $fileManagerOperations[$entityPath] = $manipulator;
            }

            foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
                if (self::isRelation($fieldConfig)) {
                    $this->addRelation(
                        $manipulator,
                        $entityName,
                        $fieldName,
                        $fieldConfig,
                        $organization,
                        $package,
                        $fileManagerOperations
                    );
                } else {
                    $manipulator->addEntityField($fieldName, $fieldConfig, $identityFields, $isRelatedEntity);
                    if ($fieldConfig['type'] === 'wysiwyg') {
                        $manipulator->addEntityField(
                            $fieldName . '_style',
                            [
                                'type' => 'wysiwyg_style',
                                'required' => false,
                                'disable_import_export' => !empty($fieldConfig['disable_import_export']),
                                'disable_data_audit' => true
                            ]
                        );
                        $manipulator->addEntityField(
                            $fieldName . '_properties',
                            [
                                'type' => 'wysiwyg_properties',
                                'required' => false,
                                'disable_import_export' => !empty($fieldConfig['disable_import_export']),
                                'disable_data_audit' => true
                            ]
                        );
                    }
                }
            }

            $postAddInterfaces = MetadataStorage::getClassMetadata($entityName, 'post_add_interfaces');
            if (!empty($postAddInterfaces)) {
                foreach ($postAddInterfaces as $interface) {
                    $manipulator->addInterface($interface);
                }
            }

            foreach ($fileManagerOperations as $path => $operationManipulator) {
                $this->fileManager->dumpFile($path, $operationManipulator->getSourceCode());
            }
        }
    }

    public static function getFieldOptions(string $fieldName, array $fieldConfig): array
    {
        $type = match ($fieldConfig['type']) {
            'html' => 'text',
            'email' => 'string',
            'percent' => 'float',
            default => $fieldConfig['type']
        };

        $data = [
            'name' => Str::asSnakeCase($fieldName),
            'type' => $type
        ];

        if ('string' === $type || 'text' === $type) {
            $defaultMax = null;
            if ('string' === $type) {
                $defaultMax = 255;
            }
            if ($length = $fieldConfig['max_length'] ?? $defaultMax) {
                $data['length'] = $length;
            }
        }
        $data['nullable'] = !($fieldConfig['required'] ?? false);

        if (($fieldConfig['default_value'] ?? null) !== null) {
            $data['options']['default'] = $fieldConfig['default_value'];
        }

        return $data;
    }

    public static function isRelation(array $fieldConfig): bool
    {
        return str_contains($fieldConfig['type'], '@')
            || str_contains($fieldConfig['type'], '\\')
            || $fieldConfig['type'] === 'enum'
            || $fieldConfig['type'] === 'enum[]'
            || $fieldConfig['type'] === 'image'
            || $fieldConfig['type'] === 'relation';
    }

    protected function addRelation(
        ClassSourceManipulator $manipulator,
        string $entityAlias,
        string $fieldName,
        array $fieldConfig,
        string $organization,
        string $package,
        array &$fileManagerOperations
    ): void {
        if ($fieldConfig['type'] === 'enum' || $fieldConfig['type'] === 'enum[]') {
            MetadataStorage::addFieldMetadata($entityAlias, $fieldName, 'enum_code', $entityAlias . '_' . $fieldName);

            return;
        }
        if ($fieldConfig['type'] === 'image') {
            return;
        }

        $entityClass = MetadataStorage::getClassName($entityAlias);
        $entityPath = MetadataStorage::getClassMetadata($entityAlias, 'entity_class_path');
        $isInternal = MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal', false);
        $isTargetRelated = MetadataStorage::getClassMetadata(
            $fieldConfig['relation_target'],
            'is_related_entity',
            false
        );
        $isNullable = !$isTargetRelated && !$fieldConfig['required'];

        $relation = $this->getEntityRelation(
            self::RELATION_TYPE_MAP[$fieldConfig['relation_type']],
            Str::asLowerCamelCase($entityAlias),
            $entityClass,
            $fieldConfig['relation_target'],
            $fieldName,
            $isNullable,
            $isInternal
        );

        $otherManipulator = null;
        if ($relation->isSelfReferencing()) {
            $otherManipulatorFilename = $entityPath;
            $otherManipulator = $manipulator;
        } elseif ($isInternal) {
            // Do not modify source code of entities outside of project
            $otherManipulatorFilename = $this->getPathOfClass($relation->getInverseClass());
            $otherManipulator = $fileManagerOperations[$otherManipulatorFilename]
                ?? $this->createClassManipulator($otherManipulatorFilename);
        }
        switch ($relation->getType()) {
            case EntityRelation::MANY_TO_ONE:
                if ($relation->getOwningClass() === $entityClass) {
                    // THIS class will receive the ManyToOne
                    $manipulator->addManyToOneRelation($relation->getOwningRelation(), $fieldName, $fieldConfig);

                    if ($relation->getMapInverseRelation()) {
                        $otherManipulator->addOneToManyRelation(
                            $relation->getInverseRelation(),
                            $fieldName,
                            ['disable_import_export' => true]
                        );

                        $inverseFieldName = Str::asSnakeCase($relation->getInverseProperty());
                        $target = $fieldConfig['relation_target'];
                        MetadataStorage::appendArrayClassMetadata(
                            $target,
                            'inverse_one_to_many',
                            [
                                'type' => 'relation',
                                'relation_type' => 'one-to-many',
                                'relation_target' => $relation->getOwningClass(),
                                'required' => $fieldConfig['required'],
                                'is_inverse' => true,
                                'inversed_by' => Str::asSnakeCase($relation->getOwningProperty())
                            ],
                            $inverseFieldName
                        );
                    }
                } else {
                    if (!MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal')) {
                        return;
                    }

                    $otherManipulatorFilename = $this->getPathOfClass($relation->getOwningClass());
                    $otherManipulator = $this->createClassManipulator($otherManipulatorFilename);
                    $inverseFieldConfig = [
                        'type' => 'relation',
                        'relation_type' => 'many-to-one',
                        'relation_target' => $relation->getInverseClass(),
                        'required' => !$relation->isNullable(),
                        'is_inverse' => true,
                        'inversed_by' => Str::asSnakeCase($relation->getInverseProperty())
                    ];

                    // The *other* class will receive the ManyToOne
                    $otherManipulator->addManyToOneRelation(
                        $relation->getOwningRelation(),
                        $fieldName,
                        $fieldConfig
                    );
                    if (!$relation->getMapInverseRelation()) {
                        throw new \Exception('Somehow a OneToMany relationship is being created, but the inverse side will not be mapped?');
                    }
                    $manipulator->addOneToManyRelation(
                        $relation->getInverseRelation(),
                        $fieldName,
                        ['disable_import_export' => true]
                    );

                    // Add field info to inverse side of relation to generate correct installer
                    $target = $fieldConfig['relation_target'];
                    $owningFieldName = Str::asSnakeCase($relation->getOwningProperty());
                    MetadataStorage::appendArrayClassMetadata(
                        $target,
                        'inverse_many_to_one',
                        $inverseFieldConfig,
                        $owningFieldName
                    );
                }

                break;
            case EntityRelation::MANY_TO_MANY:
                $from = Str::asSnakeCase(Str::pluralCamelCaseToSingular(Str::asCamelCase($fieldName)));
                $joinTable = sprintf('%s_%s_%s_to_%s', $organization, $package, $from, $entityAlias);

                MetadataStorage::addFieldMetadata($entityAlias, $fieldName, 'join_table_name', $joinTable);
                MetadataStorage::addFieldMetadata($entityAlias, $fieldName, 'join_column', $entityAlias . '_id');
                MetadataStorage::addFieldMetadata(
                    $entityAlias,
                    $fieldName,
                    'inverse_join_column',
                    MetadataStorage::getAlias($fieldConfig['relation_target']) . '_id'
                );

                $manipulator->addManyToManyRelation(
                    $relation->getOwningRelation(),
                    [
                        'join_table' => $joinTable,
                        'join_column' => MetadataStorage::getFieldMetadata(
                            $entityAlias,
                            $fieldName,
                            'join_column'
                        ),
                        'inverse_join_column' => MetadataStorage::getFieldMetadata(
                            $entityAlias,
                            $fieldName,
                            'inverse_join_column'
                        )
                    ],
                    $fieldName,
                    ['disable_import_export' => true]
                );
                if ($relation->getMapInverseRelation()) {
                    // Add information to inverse side of relation for proper translation generation
                    $target = $fieldConfig['relation_target'];
                    $targetField = Str::asSnakeCase($relation->getInverseProperty());
                    MetadataStorage::appendArrayClassMetadata(
                        $target,
                        'inverse_many_to_many',
                        [
                            'type' => 'relation',
                            'relation_type' => 'many-to-many',
                            'relation_target' => $entityClass,
                            'is_inverse' => true,
                            'inversed_by' => Str::asSnakeCase($relation->getOwningProperty())
                        ],
                        $targetField
                    );

                    $otherManipulator->addManyToManyRelation(
                        $relation->getInverseRelation(),
                        [
                            'join_table' => $joinTable,
                            'join_column' => MetadataStorage::getFieldMetadata(
                                $entityAlias,
                                $fieldName,
                                'inverse_join_column'
                            ),
                            'inverse_join_column' => MetadataStorage::getFieldMetadata(
                                $entityAlias,
                                $fieldName,
                                'join_column'
                            )
                        ],
                        $fieldName,
                        ['disable_import_export' => true]
                    );
                }

                break;
            default:
                throw new \Exception('Invalid or unsupported relation type');
        }

        // save the inverse side if it's being mapped
        if ($relation->getMapInverseRelation()) {
            $fileManagerOperations[$otherManipulatorFilename] = $otherManipulator;
        }
    }

    protected function getEntityRelation(
        string $type,
        string $generatedEntityAlias,
        string $generatedEntityClass,
        string $targetEntityClass,
        string $newFieldName,
        bool $isNullable,
        bool $isBidirectional
    ): EntityRelation {
        switch ($type) {
            case EntityRelation::MANY_TO_ONE:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setMapInverseRelation($isBidirectional);
                $relation->setOwningProperty($newFieldName);

                $relation->setIsNullable($isNullable);
                if ($relation->getMapInverseRelation()) {
                    $inverseProperty = Str::asCamelCase($newFieldName)
                        . ucfirst(Str::singularCamelCaseToPluralCamelCase($generatedEntityAlias));
                    $relation->setInverseProperty($inverseProperty);

                    // orphan removal only applies if the inverse relation is set
                    if (!$relation->isNullable()) {
                        $relation->setOrphanRemoval(true);
                    }
                }

                break;
            case EntityRelation::ONE_TO_MANY:
                // we *actually* create a ManyToOne, but populate it differently
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_ONE,
                    $targetEntityClass,
                    $generatedEntityClass
                );
                $relation->setMapInverseRelation($isBidirectional);
                $relation->setOwningProperty($generatedEntityAlias);
                if ($isBidirectional) {
                    $relation->setInverseProperty($newFieldName);
                }
                $relation->setIsNullable($isNullable);

                if (!$relation->isNullable()) {
                    $relation->setOrphanRemoval(true);
                }

                break;
            case EntityRelation::MANY_TO_MANY:
                $relation = new EntityRelation(
                    EntityRelation::MANY_TO_MANY,
                    $generatedEntityClass,
                    $targetEntityClass
                );
                $relation->setOwningProperty($newFieldName);
                $inverseProperty = Str::pluralCamelCaseToSingular(Str::asCamelCase($newFieldName))
                    . ucfirst(Str::singularCamelCaseToPluralCamelCase($generatedEntityAlias));
                $relation->setMapInverseRelation($isBidirectional);
                if ($isBidirectional) {
                    $relation->setInverseProperty($inverseProperty);
                }

                break;
            default:
                throw new \InvalidArgumentException('Unsupported relation type: ' . $type);
        }

        return $relation;
    }

    protected function getPathOfClass(string $class): string
    {
        return (new ClassDetails($class))->getPath();
    }

    protected function createClassManipulator(
        string $entityPath
    ): ClassSourceManipulator {
        return new ClassSourceManipulator(
            $this->fileManager->getFileContents($entityPath),
            $this->annotationRenderer
        );
    }
}
