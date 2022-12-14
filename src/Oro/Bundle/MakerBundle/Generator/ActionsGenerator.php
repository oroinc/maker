<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\ActionsHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates actions.yml
 */
class ActionsGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $actions = ActionsHelper::getActions($configData, $generator, $srcPath);
        if ($actions) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'actions.yml'),
                ['operations' => $actions],
                8
            );

            return true;
        }

        return false;
    }
}
