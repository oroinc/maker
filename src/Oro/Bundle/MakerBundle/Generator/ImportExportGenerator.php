<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ImportExportHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Generates ImportExportConfigurationProvider along with import_export.yml services config
 * Additionally generates reader and data converter for import-export of related entities.
 */
class ImportExportGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $uses = [
            'Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration',
            'Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface',
            'Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface'
        ];

        $importExports = [];
        $exportJobs = [];
        $prefix = $configData['options']['organization'] . '.' . $configData['options']['package'];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (!ImportExportHelper::isImportExportEnabled($entityConfig)) {
                continue;
            }

            $alias = ImportExportHelper::getAlias($configData, $entityName);
            $className = MetadataStorage::getClassName($entityName);
            $shortClassName = Str::getShortClassName($className);
            $exportJobName = ImportExportHelper::getExportJobName($configData, $entityName);

            $configClassNameDetails = $generator->createClassNameDetails(
                Str::asCamelCase($entityName) . 'ImportExportConfiguration',
                'ImportExport\\Configuration',
                'Provider'
            );
            $generator->generateClass(
                $configClassNameDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/import_export/configuration_provider.tpl.php',
                [
                    'alias' => $alias,
                    'uses' => array_merge($uses, [$className]),
                    'short_class_name' => $shortClassName,
                    'has_export_template' => false,
                    'export_job_name' => $exportJobName
                ]
            );

            $importExportSection = [
                'alias' => $alias,
                'class_name' => $className,
                'short_class_name' => $shortClassName,
                'config_class_name' => $configClassNameDetails->getFullName(),
                'suffix' => Str::asSnakeCase($shortClassName),
                'reader_class_name' => null,
                'data_converter_class_name' => null
            ];

            if (!empty($entityConfig['configuration']['is_related_entity'])) {
                $inverseRelation = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', []);
                $relationOwnerField = Str::asLowerCamelCase(array_keys($inverseRelation)[0]);
                $readerClassNameDetails = $generator->createClassNameDetails(
                    Str::asCamelCase($entityName),
                    'ImportExport\\Reader',
                    'Reader'
                );
                $generator->generateClass(
                    $readerClassNameDetails->getFullName(),
                    __DIR__ . '/../Resources/skeleton/import_export/reader.tpl.php',
                    [
                        'short_class_name' => $shortClassName,
                        'relation_owner_field' => $relationOwnerField
                    ]
                );
                $importExportSection['reader_class_name'] = $readerClassNameDetails->getFullName();

                $dataConverterClassNameDetails = $generator->createClassNameDetails(
                    Str::asCamelCase($entityName),
                    'ImportExport\\DataConverter',
                    'DataConverter'
                );
                $generator->generateClass(
                    $dataConverterClassNameDetails->getFullName(),
                    __DIR__ . '/../Resources/skeleton/import_export/data_converter.tpl.php',
                    [
                        'short_class_name' => $shortClassName,
                        'relation_owner_field' => $relationOwnerField
                    ]
                );
                $importExportSection['data_converter_class_name'] = $dataConverterClassNameDetails->getFullName();

                $exportJobs[$exportJobName] = [
                    'suffix' => Str::asSnakeCase($shortClassName)
                ];
            }
            $importExports[] = $importExportSection;
        }

        if ($exportJobs) {
            $generator->generateOrModifyYamlFile(
                LocationMapper::getConfigPath($srcPath, 'batch_jobs.yml'),
                __DIR__ . '/../Resources/skeleton/import_export/batch_jobs.yml.tpl.php',
                [
                    'data' => $exportJobs,
                    'prefix' => $prefix
                ],
                true
            );
        }

        if ($importExports) {
            $generator->generateOrModifyYamlFile(
                LocationMapper::getServicesConfigPath($srcPath, 'import_export.yml'),
                __DIR__ . '/../Resources/skeleton/import_export/import_export.yml.tpl.php',
                [
                    'data' => $importExports,
                    'prefix' => $prefix
                ]
            );
            MetadataStorage::appendGlobalMetadata('service_config_files', 'import_export.yml');

            return true;
        }

        return false;
    }
}
