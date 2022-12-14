<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\GridHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates datagrids.yml
 */
class GridGenerator implements GeneratorInterface
{
    public function __construct(
        private GridHelper $gridHelper
    ) {
    }

    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = $this->gridHelper->getGridsConfiguration($configData);
        if ($config) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'datagrids.yml'),
                $config,
                7
            );

            return true;
        }

        return false;
    }
}
