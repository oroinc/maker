<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ApiHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates api.yml
 */
class ApiGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $config = ApiHelper::getConfiguration($configData);
        if ($config) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'api.yml'),
                ['api' => $config],
                8,
                true
            );

            return true;
        }

        return false;
    }
}
