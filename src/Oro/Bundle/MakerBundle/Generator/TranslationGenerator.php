<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Generates messages.en.yml
 */
class TranslationGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $translations = TranslationHelper::getTranslationStrings($configData);
        if ($translations) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getTranslationsPath($srcPath, 'messages.en.yml'),
                $translations,
                10,
                true
            );

            return true;
        }

        return false;
    }
}
