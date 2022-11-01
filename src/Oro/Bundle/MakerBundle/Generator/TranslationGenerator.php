<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates messages.en.yml
 */
class TranslationGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $messageLocation = LocationMapper::getTranslationsPath($srcPath, 'messages.en.yml');

        $content = '';
        if (is_file($messageLocation)) {
            $content = file_get_contents($messageLocation) . PHP_EOL;
        }
        $content .= Yaml::dump(TranslationHelper::getTranslationStrings($configData), 10, 4, Yaml::DUMP_NULL_AS_TILDE);

        $generator->dumpFile($messageLocation, $content);

        return true;
    }
}
