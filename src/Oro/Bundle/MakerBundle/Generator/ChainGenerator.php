<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Symfony\Bundle\MakerBundle\Generator;

/**
 * Call chain of generators
 */
class ChainGenerator implements GeneratorInterface
{
    /**
     * @var iterable|GeneratorInterface[]
     */
    private iterable $generators;

    public function __construct(iterable $generators)
    {
        $this->generators = $generators;
    }

    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        foreach ($this->generators as $codeGenerator) {
            $codeGenerator->generate($generator, $configData, $srcPath);
        }

        return true;
    }
}
