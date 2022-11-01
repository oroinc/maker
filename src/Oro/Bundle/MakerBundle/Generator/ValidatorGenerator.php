<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ValidationHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates validation.yml
 */
class ValidatorGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = ValidationHelper::getValidatorConfiguration($configData);
        if ($config) {
            $generator->dumpFile(
                LocationMapper::getConfigPath($srcPath, 'validation.yml'),
                Yaml::dump($config, 6, 4, Yaml::DUMP_NULL_AS_TILDE)
            );

            return true;
        }

        return false;
    }
}
