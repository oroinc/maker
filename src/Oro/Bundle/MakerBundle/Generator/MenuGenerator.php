<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\MenuHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates navigation.yml
 */
class MenuGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = MenuHelper::getMenuConfig($configData);
        if ($config) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'navigation.yml'),
                $config,
                11,
                true
            );

            return true;
        }

        return false;
    }
}
