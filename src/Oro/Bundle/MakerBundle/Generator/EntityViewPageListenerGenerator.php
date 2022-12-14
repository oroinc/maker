<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\EntityDependencyHelper;
use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;

/**
 * Generates view page event listener to render -to-many relation grid for external entity.
 */
class EntityViewPageListenerGenerator implements GeneratorInterface
{
    public function __construct(
        private ComposerAutoloaderFinder $autoloaderFinder
    ) {
    }

    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $eventListeners = [];
        $templatePathPrefix = LocationMapper::getTemplateTwigPathPrefix();
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
                if ($fieldConfig['type'] !== 'relation'
                    || MetadataStorage::getClassMetadata($fieldConfig['relation_target'], 'is_internal', false)
                ) {
                    continue;
                }

                $inverseFieldName = EntityDependencyHelper::getInverseFieldName($entityName, $fieldName);
                $gridName = MetadataStorage::getFieldMetadata(
                    $fieldConfig['relation_target'],
                    $inverseFieldName,
                    'relation_grid_name'
                );
                if (!$gridName) {
                    continue;
                }

                $viewPageId = $this->getViewPageId($fieldConfig['relation_target']);
                if (!$viewPageId) {
                    continue;
                }

                $shortRelationClass = Str::getShortClassName($fieldConfig['relation_target']);
                $listenerClassDetails = $generator->createClassNameDetails(
                    $shortRelationClass,
                    'EventListener',
                    'ViewPageListener'
                );

                $eventListeners[$listenerClassDetails->getFullName()]['short_relation_class'] = $shortRelationClass;
                $eventListeners[$listenerClassDetails->getFullName()]['view_page_id'] = $viewPageId;
                $eventListeners[$listenerClassDetails->getFullName()]['sections'][] = [
                    'section_label' => TranslationHelper::getEntityPluralLabel($entityName),
                    'grid_name' => $gridName
                ];
            }
        }

        if ($eventListeners) {
            foreach ($eventListeners as $listenerClass => $data) {
                $generator->generateClass(
                    $listenerClass,
                    __DIR__ . '/../Resources/skeleton/crud/view_event_listener.tpl.php',
                    [
                        'short_relation_class' => $data['short_relation_class'],
                        'template_path_prefix' => $templatePathPrefix,
                        'sections' => $data['sections']
                    ]
                );
            }

            if (!is_file(LocationMapper::getTemplatePath($srcPath, 'includes/relationGrid.html.twig'))) {
                $generator->generateFile(
                    LocationMapper::getTemplatePath($srcPath, 'includes/relationGrid.html.twig'),
                    __DIR__ . '/../Resources/skeleton/crud/templates/includes/relationGrid.html.twig.tpl.php'
                );
            }

            $generator->generateOrModifyYamlFile(
                LocationMapper::getServicesConfigPath($srcPath, 'event_listeners.yml'),
                __DIR__ . '/../Resources/skeleton/crud/event_listeners.yml.tpl.php',
                [
                    'view_event_listeners' => $eventListeners
                ]
            );
            MetadataStorage::appendGlobalMetadata('service_config_files', 'event_listeners.yml');
        }

        return true;
    }

    private function getViewPageId(string $className): ?string
    {
        $classLoader = $this->autoloaderFinder->getClassLoader();
        $filePath = $classLoader->findFile($className);

        $viewTemplatePath = str_replace('/Entity/', '/Resources/views/', $filePath);
        $viewTemplatePath = str_replace('.php', '/view.html.twig', $viewTemplatePath);
        if (!is_readable($viewTemplatePath)) {
            return null;
        }

        $viewTemplate = file_get_contents($viewTemplatePath);
        preg_match('/{%\s*set\s+id\s*=\s*(\'|")([0-9a-zA-Z-]+)(\'|")\s*%}/', $viewTemplate, $matches);
        if ($matches) {
            return $matches[2];
        }

        return null;
    }
}
