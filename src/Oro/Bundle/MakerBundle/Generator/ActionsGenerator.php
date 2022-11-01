<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ActionsHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates actions.yml
 */
class ActionsGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $actions = ActionsHelper::getActions($configData, $generator, $srcPath);
        if ($actions) {
            $generator->dumpFile(
                LocationMapper::getOroConfigPath($srcPath, 'actions.yml'),
                Yaml::dump(['operations' => $actions], 8, 4, Yaml::DUMP_NULL_AS_TILDE)
            );

            return true;
        }

        return false;
    }
}
