<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\GridHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

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
        $generator->dumpFile(
            LocationMapper::getOroConfigPath($srcPath, 'datagrids.yml'),
            Yaml::dump($this->gridHelper->getGridsConfiguration($configData), 7, 4, Yaml::DUMP_NULL_AS_TILDE)
        );

        return true;
    }
}
