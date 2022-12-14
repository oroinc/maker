<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates the DI extension class for bundle aware structure
 * or register services in config.yml for bundle-less structure.
 */
class DiExtensionGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $this->registerServices($srcPath);
        } else {
            $this->generateDiExtension($configData, $generator);
        }

        return true;
    }

    private function generateDiExtension(array $configData, Generator $generator): void
    {
        $bundlePrefix = Str::asCamelCase($configData['options']['organization'])
            . Str::asCamelCase($configData['options']['package']);
        $extensionClassNameDetails = $generator->createClassNameDetails(
            $bundlePrefix,
            'DependencyInjection',
            'Extension'
        );

        /** @var FileManager $fileManager */
        $fileManager = $generator->getFileManager();
        $extensionPath = $fileManager->getRelativePathForFutureClass($extensionClassNameDetails->getFullName());
        $configFiles = MetadataStorage::getGlobalMetadata('service_config_files');
        if ($fileManager->fileExists($extensionPath)) {
            $classContents = $fileManager->getFileContents($extensionPath);
            preg_match_all("/->load\(['\"]([a-zA-Z0-9_]+\.yml)['\"]\)/", $classContents, $matches);
            $existingConfigFiles = $matches[1] ?? [];
            $toAdd = array_diff($configFiles, $existingConfigFiles);
            if ($toAdd) {
                $calls = $fileManager->parseTemplate(
                    __DIR__ . '/../Resources/skeleton/bundle/include/extension_load_files.tpl.php',
                    [
                        'config_files' => $toAdd
                    ]
                );
                $search = "'/../Resources/config'));" . PHP_EOL;
                $classContents = str_replace($search, $search . $calls, $classContents);
                $generator->dumpFile($extensionPath, $classContents);
            }
        } else {
            $generator->generateClass(
                $extensionClassNameDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/bundle/extension.tpl.php',
                [
                    'config_files' => $configFiles
                ]
            );
        }
    }

    /**
     * Add services import to config.yml if not yet there
     */
    private function registerServices(string $srcPath): void
    {
        $configFile = dirname($srcPath) . '/config/config.yml';
        if (!is_file($configFile)) {
            return;
        }

        $configContent = file_get_contents($configFile);
        $configData = Yaml::parse($configContent);

        $hasServices = false;
        $importsPosition = 0;
        $contentRows = explode(PHP_EOL, $configContent);
        if (!array_key_exists('imports', $configData)) {
            array_unshift($contentRows, 'imports:');
        } else {
            $importsPosition = array_search('imports:', $contentRows);
            $imports = $configData['imports'];
            foreach ($imports as $import) {
                if ($import['resource'] === 'services/') {
                    $hasServices = true;
                    break;
                }
            }
        }

        if (!$hasServices) {
            // Add services import right after imports.
            array_splice($contentRows, $importsPosition + 1, 0, "    - { resource: 'services/' }");
        }

        file_put_contents($configFile, implode(PHP_EOL, $contentRows));
    }
}
