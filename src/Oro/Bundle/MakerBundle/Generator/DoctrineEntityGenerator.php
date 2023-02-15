<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MakerBundle\Helper\CrudHelper;
use Oro\Bundle\MakerBundle\Helper\EntityDependencyHelper;
use Oro\Bundle\MakerBundle\Helper\EntityInstallerHelper;
use Oro\Bundle\MakerBundle\Helper\OroEntityConfigHelper;
use Oro\Bundle\MakerBundle\Helper\OroEntityHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Renderer\AnnotationRenderer;
use Oro\Bundle\MakerBundle\Util\FileManager;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * Generates doctrine entities and repository classes for them.
 * Generates installers and ORM data migrations for enums.
 */
class DoctrineEntityGenerator implements GeneratorInterface
{
    private AnnotationRenderer $annotationRenderer;
    private EntityInstallerHelper $installerHelper;
    private OroEntityHelper $entityHelper;

    public function __construct(
        AnnotationRenderer $annotationRenderer,
        EntityInstallerHelper $installerHelper,
        OroEntityHelper $entityHelper
    ) {
        $this->annotationRenderer = $annotationRenderer;
        $this->installerHelper = $installerHelper;
        $this->entityHelper = $entityHelper;
    }

    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $hasChanges = $this->generateEntityClasses($generator, $configData, $srcPath);
        $this->generateInstaller($generator, $configData);
        $this->generateDataMigrations($generator, $configData);

        return $hasChanges;
    }

    public function generateEntityClasses(
        Generator $generator,
        array &$configData,
        string $srcPath
    ): bool {
        $prefix = CrudHelper::getBundlePrefix($configData);
        $repositories = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $entityClassDetails = $generator->createClassNameDetails(
                $entityName,
                'Entity\\'
            );
            $entityNamePrefixedWithBundle = $prefix . '_' . Str::asSnakeCase($entityName);
            $entityFQCN = $entityClassDetails->getFullName();

            // Register alias as early as possible
            MetadataStorage::registerClassAlias($entityName, $entityFQCN);
            MetadataStorage::addClassMetadata($entityFQCN, 'is_internal', true);
            MetadataStorage::addClassMetadata(
                $entityFQCN,
                'is_related_entity',
                !empty($entityConfig['configuration']['is_related_entity'])
            );
            MetadataStorage::addClassMetadata($entityFQCN, 'prefix', $entityNamePrefixedWithBundle);
            MetadataStorage::addClassMetadata($entityFQCN, 'table_name', $entityNamePrefixedWithBundle);
            MetadataStorage::addClassMetadata(
                $entityFQCN,
                'id_info',
                ['field_name' => 'id', 'field_type' => 'integer']
            );

            $entityExtendClassDetails = $generator->createClassNameDetails(
                'extend_' . $entityName,
                'Model\\',
            );
            $repoClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeName(),
                'Entity\\Repository\\',
                'Repository'
            );

            $extendEntityFQCN = $entityExtendClassDetails->getFullName();
            $traits = [];
            $interfaces = [];
            $uses = [
                'Doctrine\ORM\Mapping as ORM',
                'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config',
                'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField',
                $extendEntityFQCN
            ];
            EntityDependencyHelper::configureTraitsAndInterfaces($entityName, $entityConfig, $traits, $interfaces);

            $entityPath = $generator->generateClass(
                $entityFQCN,
                __DIR__ . '/../Resources/skeleton/doctrine/entity.tpl.php',
                [
                    'entity_short_name' => $entityClassDetails->getShortName(),
                    'extend_entity_class_name' => Str::getShortClassName($extendEntityFQCN),
                    'uses' => $uses,
                    'traits' => $traits,
                    'interfaces' => $interfaces,
                    'entity_annotations' => [
                        $this->annotationRenderer->render(
                            'ORM\\Entity',
                            ['repositoryClass' => $repoClassDetails->getFullName()]
                        ),
                        $this->annotationRenderer->render(
                            'ORM\\Table',
                            ['name' => $entityNamePrefixedWithBundle]
                        ),
                        $this->annotationRenderer->render(
                            'Config',
                            OroEntityConfigHelper::getConfig($entityName, $entityConfig, $generator)
                        )
                    ]
                ]
            );
            MetadataStorage::addClassMetadata($entityFQCN, 'entity_class_path', $entityPath);

            $generator->generateClass(
                $entityExtendClassDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/doctrine/entity_extend.tpl.php',
                [
                    'entity_short_name' => $entityClassDetails->getShortName()
                ]
            );

            $shortEntityClass = Str::getShortClassName($entityFQCN);
            $generator->generateClass(
                $repoClassDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/doctrine/repository.tpl.php',
                [
                    'uses' => [
                        'Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository',
                        'Doctrine\Persistence\ManagerRegistry',
                        $entityFQCN
                    ],
                    'entity_class_name' => $shortEntityClass
                ]
            );
            $repositories[] = $repoClassDetails->getFullName();
        }

        foreach ($configData['entities'] as $entityName => &$entityConfig) {
            foreach ($entityConfig['fields'] as &$fieldConfig) {
                $this->resolveRelationConfig($fieldConfig);
            }
            unset($fieldConfig);
            MetadataStorage::addClassMetadata($entityName, 'entity_fields', $entityConfig['fields']);
        }
        unset($entityConfig);

        if ($repositories) {
            $generator->generateOrModifyYamlFile(
                LocationMapper::getServicesConfigPath($srcPath, 'repositories.yml'),
                __DIR__ . '/../Resources/skeleton/doctrine/repositories.yml.tpl.php',
                [
                    'repositories' => $repositories
                ]
            );
        }

        $generator->writeChanges();

        $this->entityHelper->fillEntityFields(
            $configData['entities'],
            $configData['options']['organization'],
            $configData['options']['package']
        );

        if ($repositories) {
            MetadataStorage::appendGlobalMetadata('service_config_files', 'repositories.yml');

            return true;
        }

        return false;
    }

    public function generateInstaller(
        Generator $generator,
        array $configData
    ): void {
        $bundlePrefix = Str::asCamelCase($configData['options']['organization'])
            . Str::asCamelCase($configData['options']['package']);

        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $packageName = Str::asCamelCase(CrudHelper::getBundlePrefix($configData));
            $migrationNamespace = 'Migrations\\' . $packageName . '\\Schema';
        } else {
            $migrationNamespace = 'Migrations\\Schema';
        }

        $traits = [];
        $interfaces = [];
        $uses = [
            'Doctrine\DBAL\Schema\Schema',
            'Oro\Bundle\MigrationBundle\Migration\QueryBag',
            'Oro\Bundle\EntityExtendBundle\Migration\OroOptions'
        ];
        $this->installerHelper->configureTraitsAndInterfaces($configData, $traits, $interfaces, $uses);
        $templateVars = [
            'tables_config' => $this->installerHelper->getTablesConfig($configData['entities'], $uses),
            'requires_extend_extension' => $this->installerHelper
                ->isExtendExtensionRequired($configData['entities']),
            'uses' => array_unique($uses),
            'traits' => array_unique($traits),
            'interfaces' => array_unique($interfaces)
        ];
        $this->writeInstaller($generator, $templateVars, $migrationNamespace, $bundlePrefix);
    }

    private function writeInstaller(
        Generator $generator,
        array $templateVars,
        string $migrationNamespace,
        string $bundlePrefix
    ): void {
        $installerClassNameDetails = $generator->createClassNameDetails(
            $bundlePrefix,
            $migrationNamespace,
            'Installer'
        );
        /** @var FileManager $fileManager */
        $fileManager = $generator->getFileManager();
        $installerPath = $fileManager->getRelativePathForFutureClass($installerClassNameDetails->getFullName());
        if ($fileManager->fileExists($installerPath)) {
            $calls = $fileManager->parseTemplate(
                __DIR__ . '/../Resources/skeleton/doctrine/include/installer_calls.tpl.php',
                $templateVars
            );

            $classContents = $fileManager->getFileContents($installerPath);

            $file = PhpFile::fromCode($classContents);
            $classes = $file->getClasses();
            /** @var ClassType $class */
            $class = reset($classes);
            // Append new up calls after execution of existing calls
            $existingCalls = $class->getMethod('up')->getBody();
            $class->getMethod('up')->setBody($existingCalls . PHP_EOL . $calls);

            // Increase migration version
            preg_match('/v(\d[\d_]+\d)/', $class->getMethod('getMigrationVersion')->getBody(), $matches);
            $versionData = explode('_', $matches[1]);
            $versionData[array_key_last($versionData)]++;
            $newVersion = 'v' . implode('_', $versionData);
            $class->getMethod('getMigrationVersion')->setBody(sprintf("return '%s';", $newVersion));

            // Generate migration script
            $migrationClassNameDetails = $generator->createClassNameDetails(
                'AddNewTablesTo',
                $migrationNamespace . '\\' . $newVersion,
                $bundlePrefix
            );
            $migrationVars = $templateVars;
            array_unshift($migrationVars['interfaces'], 'Oro\Bundle\MigrationBundle\Migration\Migration');
            $generator->generateClass(
                $migrationClassNameDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/doctrine/migration.tpl.php',
                $migrationVars
            );

            // Add not present use, implements and traits
            $namespaces = $file->getNamespaces();
            /** @var PhpNamespace $namespace */
            $namespace = reset($namespaces);
            foreach ($templateVars['uses'] as $use) {
                if (!str_contains($classContents, $use . ';')) {
                    $namespace->addUse($use);
                }
            }
            foreach ($templateVars['interfaces'] as $interface) {
                if (!str_contains($classContents, $interface . ';')) {
                    $namespace->addUse($interface);
                    $class->addImplement($interface);
                }
            }
            foreach ($templateVars['traits'] as $trait) {
                if (!str_contains($classContents, $trait . ';')) {
                    $namespace->addUse($trait);
                    $class->addTrait($trait);
                }
            }

            $printer = new PsrPrinter();
            foreach ($file->getNamespaces() as $namespace) {
                $namespace->setBracketedSyntax(false);
            }
            $classContents = $printer->printFile($file);

            // Add extend extension if not present
            if (!empty($templateVars['requires_extend_extension']) && !$class->hasMethod('setExtendExtension')) {
                $extendExtension = $fileManager->parseTemplate(
                    __DIR__ . '/../Resources/skeleton/doctrine/include/installer_extend_extension.tpl.php',
                    $templateVars
                );
                $versionStr = '    public function getMigrationVersion()';
                $classContents = str_replace($versionStr, $extendExtension . PHP_EOL . $versionStr, $classContents);
            }

            // Add generated methods to before the closing }
            $classContents = trim($classContents);
            $methods = $fileManager->parseTemplate(
                __DIR__ . '/../Resources/skeleton/doctrine/include/installer_functions.tpl.php',
                $templateVars
            );
            $classContents = substr($classContents, 0, -1) . $methods . '}' . PHP_EOL;

            $generator->dumpFile($installerPath, $classContents);
        } else {
            array_unshift($templateVars['interfaces'], 'Oro\Bundle\MigrationBundle\Migration\Installation');
            $generator->generateClass(
                $installerClassNameDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/doctrine/installer.tpl.php',
                $templateVars,
            );
        }
    }

    public function generateDataMigrations(Generator $generator, array $configData): void
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $packageName = Str::asCamelCase(CrudHelper::getBundlePrefix($configData));
            $migrationNamespace = 'Migrations\\' . $packageName . '\\Data\\ORM';
        } else {
            $migrationNamespace = 'Migrations\\Data\\ORM';
        }
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
                if ($fieldConfig['type'] !== 'enum' && $fieldConfig['type'] !== 'enum[]') {
                    continue;
                }

                $enumMigrationClassNameDetails = $generator->createClassNameDetails(
                    'Load'
                    . Str::asCamelCase($entityName)
                    . ucfirst(Str::singularCamelCaseToPluralCamelCase(Str::asCamelCase($fieldName))),
                    $migrationNamespace,
                    'Data'
                );

                $values = [];
                foreach ($fieldConfig['values'] ?? [] as $value) {
                    $values[ExtendHelper::buildEnumValueId($value)] = $value;
                }

                $generator->generateClass(
                    $enumMigrationClassNameDetails->getFullName(),
                    __DIR__ . '/../Resources/skeleton/doctrine/enum_data_migration.tpl.php',
                    [
                        'values' => $values,
                        'default_value' => $fieldConfig['default_value'],
                        'enum_code' => $entityName . '_' . $fieldName
                    ]
                );
            }
        }
    }

    private function resolveRelationConfig(array &$fieldConfig): void
    {
        // Resolve inner references
        if (str_starts_with($fieldConfig['type'], '@')) {
            if (empty($fieldConfig['relation_type'])) {
                if (str_ends_with($fieldConfig['type'], '[]')) {
                    $fieldConfig['relation_type'] = 'one-to-many';
                } else {
                    $fieldConfig['relation_type'] = 'many-to-one';
                }
            }

            $target = str_replace(['@', '[', ']'], '', $fieldConfig['type']);
            // Resolve short syntax where entity class is used as type to full config
            if (str_contains($target, '\\')) {
                if (!class_exists($target)) {
                    throw new \InvalidArgumentException('Unknown entity class used as relation target');
                }
                $fieldConfig['relation_target'] = $target;
            } else {
                $target = Str::asSnakeCase($target);
                if (MetadataStorage::getClassNameByAlias($target) === null) {
                    throw new \InvalidArgumentException('Unknown entity reference provided');
                }
                $fieldConfig['relation_target'] = MetadataStorage::getClassNameByAlias($target);
            }

            $fieldConfig['type'] = 'relation';
        }
    }
}
