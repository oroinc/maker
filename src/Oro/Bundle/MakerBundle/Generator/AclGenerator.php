<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\AclHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates acls.yml
 */
class AclGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $acls = AclHelper::getAcls($configData);
        if ($acls) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'acls.yml'),
                ['acls' => $acls]
            );

            return true;
        }

        return false;
    }
}
