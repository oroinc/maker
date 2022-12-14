<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ValidationHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates validation.yml
 */
class ValidatorGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = ValidationHelper::getValidatorConfiguration($configData);
        if ($config) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getConfigPath($srcPath, 'validation.yml'),
                $config,
                6
            );

            return true;
        }

        return false;
    }
}
