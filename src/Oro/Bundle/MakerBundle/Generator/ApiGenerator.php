<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ApiHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates api.yml
 */
class ApiGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = ApiHelper::getConfiguration($configData);
        if ($config) {
            $generator->dumpFile(
                LocationMapper::getOroConfigPath($srcPath, 'api.yml'),
                Yaml::dump(['api' => $config], 8, 4, Yaml::DUMP_NULL_AS_TILDE)
            );

            return true;
        }

        return false;
    }
}
