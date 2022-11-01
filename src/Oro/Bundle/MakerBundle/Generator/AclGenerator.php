<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\AclHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates acls.yml
 */
class AclGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $acls = AclHelper::getAcls($configData);
        if ($acls) {
            $generator->dumpFile(
                LocationMapper::getOroConfigPath($srcPath, 'acls.yml'),
                Yaml::dump(['acls' => $acls], 4, 4, Yaml::DUMP_NULL_AS_TILDE)
            );

            return true;
        }

        return false;
    }
}
