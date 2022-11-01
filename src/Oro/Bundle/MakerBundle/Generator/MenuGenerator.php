<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\MenuHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates navigation.yml
 */
class MenuGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = MenuHelper::getMenuConfig($configData);

        if ($config) {
            $generator->dumpFile(
                LocationMapper::getOroConfigPath($srcPath, 'navigation.yml'),
                Yaml::dump($config, 11, 4, Yaml::DUMP_NULL_AS_TILDE)
            );

            return true;
        }

        return false;
    }
}
