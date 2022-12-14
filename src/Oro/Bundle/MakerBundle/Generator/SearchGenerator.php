<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\CrudHelper;
use Oro\Bundle\MakerBundle\Helper\SearchHelper;
use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Generates search.yml.
 * Generates searchResult.html.twig template for each entity.
 */
class SearchGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (!CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }
            if (empty($entityConfig['configuration']['configure_search'])) {
                continue;
            }

            $entityClassName = MetadataStorage::getClassName($entityName);
            $shortClassName = Str::getShortClassName($entityClassName);

            $generator->generateFile(
                LocationMapper::getEntityTemplatePath($srcPath, $shortClassName, 'searchResult.html.twig'),
                __DIR__ . '/../Resources/skeleton/search/search_result.html.twig.tpl.php',
                [
                    'entity_class_name' => $entityClassName,
                    'entity_label' => TranslationHelper::getEntityLabel($entityName)
                ]
            );
        }

        $config = SearchHelper::getSearchConfig($configData);
        if ($config) {
            $generator->addOrModifyYamlFile(
                LocationMapper::getOroConfigPath($srcPath, 'search.yml'),
                $config,
                8
            );

            return true;
        }

        return false;
    }
}
