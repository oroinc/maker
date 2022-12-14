<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Generates the bundle class for bundle aware structure.
 */
class BundleGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        // Skip bundle class generation for bundle-less structure
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            return true;
        }

        $bundlePrefix = Str::asCamelCase($configData['options']['organization'])
            . Str::asCamelCase($configData['options']['package']);
        $bundleClassNameDetails = $generator->createClassNameDetails($bundlePrefix, '', 'Bundle');

        try {
            $generator->generateClass(
                $bundleClassNameDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/bundle/bundle.tpl.php'
            );

            $generator->generateFile(
                $srcPath . '/Resources/config/oro/bundles.yml',
                __DIR__ . '/../Resources/skeleton/bundle/bundles.yml.tpl.php',
                ['bundle_class_name' => $bundleClassNameDetails->getFullName()]
            );
        } catch (RuntimeCommandException $e) {
            // Skip existing file exception to unlock partial generation
        }

        MetadataStorage::setGlobalMetadata('bundle_class_name', $bundleClassNameDetails->getFullName());

        return true;
    }
}
