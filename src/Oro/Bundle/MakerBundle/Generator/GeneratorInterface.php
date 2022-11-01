<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Symfony\Bundle\MakerBundle\Generator;

/**
 * Oro maker generator interface.
 */
interface GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool;
}
