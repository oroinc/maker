<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Symfony\Bundle\MakerBundle\Generator;

/**
 * Interface for code generators in the Oro maker bundle.
 *
 * Implementations of this interface generate various configuration files and code artifacts
 * using the {@see Generator} from Symfony's MakerBundle.
 */
interface GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool;
}
