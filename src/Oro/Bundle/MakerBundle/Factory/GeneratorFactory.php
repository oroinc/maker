<?php

namespace Oro\Bundle\MakerBundle\Factory;

use Oro\Bundle\MakerBundle\Generator\Generator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

/**
 * Creates Generator instance with support of bundle-aware structure.
 */
class GeneratorFactory
{
    private PhpCompatUtil $phpCompatUtil;
    private TemplateComponentGenerator $templateComponentGenerator;

    public function __construct(
        PhpCompatUtil $phpCompatUtil,
        TemplateComponentGenerator $templateComponentGenerator
    ) {
        $this->phpCompatUtil = $phpCompatUtil;
        $this->templateComponentGenerator = $templateComponentGenerator;
    }

    public function createGenerator(FileManager $fileManager, array $configData): Generator
    {
        return new Generator(
            $fileManager,
            $this->getBundleAwareNamespace($configData),
            $this->phpCompatUtil,
            $this->templateComponentGenerator
        );
    }

    private function getBundleAwareNamespace(array $configData): string
    {
        return implode(
            '\\',
            [
                Str::asCamelCase($configData['options']['organization']),
                'Bundle',
                Str::asCamelCase($configData['options']['package']) . 'Bundle'
            ]
        );
    }
}
