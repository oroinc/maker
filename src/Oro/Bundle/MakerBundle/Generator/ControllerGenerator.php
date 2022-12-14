<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\CrudHelper;
use Oro\Bundle\MakerBundle\Helper\ImportExportHelper;
use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Generates controller classes for entities with enabled CRUD
 * Generates required twig templates (index, view, update)
 * Generates routing.yml or registers controller in config/routing.yml for bundle-less structure
 * Generates services for controllers in controllers.yml
 */
class ControllerGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $controllers = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
            $controllerClassDetails = $generator->createClassNameDetails(
                Str::asCamelCase($entityName),
                'Controller',
                'Controller'
            );

            $entityClass = MetadataStorage::getClassName($entityName);
            $formType = MetadataStorage::getClassMetadata($entityName, 'form_type');
            $uses = [
                'Oro\Bundle\FormBundle\Model\UpdateHandlerFacade',
                'Oro\Bundle\SecurityBundle\Annotation\Acl',
                'Oro\Bundle\SecurityBundle\Annotation\AclAncestor',
                'Sensio\Bundle\FrameworkExtraBundle\Configuration\Template',
                'Symfony\Bundle\FrameworkBundle\Controller\AbstractController',
                'Symfony\Component\HttpFoundation\RedirectResponse',
                'Symfony\Component\HttpFoundation\Request',
                'Symfony\Component\Routing\Annotation\Route',
                'Symfony\Contracts\Translation\TranslatorInterface',
                $entityClass,
                $formType
            ];

            $entityAlias = Str::asSnakeCase($entityName);
            $detachActions = CrudHelper::getDetachActions($entityName, $entityConfig, $uses);
            $shortClassName = Str::getShortClassName($entityClass);
            if (!$detachActions && !CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }

            $generator->generateClass(
                $controllerClassDetails->getFullName(),
                __DIR__ . '/../Resources/skeleton/crud/controller.tpl.php',
                [
                    'is_crud_enabled' => CrudHelper::isCrudEnabled($entityConfig),
                    'entity_class' => $entityClass,
                    'template_path_prefix' => LocationMapper::getEntityTemplateTwigPathPrefix($shortClassName),
                    'short_class_name' => $shortClassName,
                    'entity_name' => $entityAlias,
                    'route_prefix' => $prefix,
                    'form_type' => Str::getShortClassName($formType),
                    'uses' => $uses,
                    'saved_message' => TranslationHelper::getSaveMessage($configData, $entityName),
                    'detach_actions' => $detachActions
                ]
            );
            $routes = CrudHelper::getRouteNames($entityName);
            $controllers[] = $controllerClassDetails->getFullName();
            $templates = [
                'index' => [
                    'grid_name' => MetadataStorage::getClassMetadata($entityName, 'grid_name'),
                    'create_acl' => $routes['create'],
                    'routes' => $routes,
                    'entity_label' => TranslationHelper::getEntityLabel($entityName),
                    'entity_plural_label' => TranslationHelper::getEntityPluralLabel($entityName),
                    'import_export_alias' => ImportExportHelper::getAlias($configData, $entityName)
                ],
                'view' => [
                    'entity_title_expression' => CrudHelper::getEntityTitleExpression(),
                    'routes' => $routes,
                    'entity_plural_label' => TranslationHelper::getEntityPluralLabel($entityName),
                    'view_page_blocks' => CrudHelper::getViewPageBlocks($configData, $entityName),
                    'block_buttons' => CrudHelper::getViewPageButtons($configData, $entityName),
                    'page_id' => str_replace('_', '-', $prefix) . '-view',
                ],
                'update' => [
                    'page_id' => str_replace('_', '-', $prefix) . '-edit',
                    'routes' => $routes,
                    'update_acl' => $routes['update'],
                    'create_acl' => $routes['create'],
                    'entity_title_expression' => CrudHelper::getEntityTitleExpression(),
                    'entity_label' => TranslationHelper::getEntityLabel($entityName),
                    'entity_plural_label' => TranslationHelper::getEntityPluralLabel($entityName),
                    'update_page_blocks' => CrudHelper::getUpdatePageBlocks($configData, $entityName),
                ],
            ];

            foreach ($templates as $template => $variables) {
                $generator->generateFile(
                    LocationMapper::getEntityTemplatePath($srcPath, $shortClassName, $template . '.html.twig'),
                    __DIR__ . '/../Resources/skeleton/crud/templates/' . $template . '.html.twig.tpl.php',
                    $variables
                );
            }
        }
        $this->registerControllers($controllers, $srcPath, $configData, $generator);

        return true;
    }

    private function registerControllers(
        array $controllers,
        string $srcPath,
        array $configData,
        Generator $generator
    ): void {
        if (!$controllers) {
            return;
        }
        $this->registerRouting($configData, $generator, $srcPath);

        $generator->generateOrModifyYamlFile(
            LocationMapper::getServicesConfigPath($srcPath, 'controllers.yml'),
            __DIR__ . '/../Resources/skeleton/crud/controllers.yml.tpl.php',
            [
                'controllers' => $controllers
            ]
        );
        MetadataStorage::appendGlobalMetadata('service_config_files', 'controllers.yml');
    }

    private function registerRouting(array $configData, Generator $generator, string $srcPath): void
    {
        if (MetadataStorage::getGlobalMetadata('bundle_less')) {
            $routingResource = '../src/Controller';
            $routePrefix = 'app';
            $prefix = '%web_backend_prefix%/app';
        } else {
            $bundleClass = MetadataStorage::getGlobalMetadata('bundle_class_name');
            $routingResource = '@' . Str::getShortClassName($bundleClass) . '/Controller';
            $routePrefix = CrudHelper::getBundlePrefix($configData);
            $prefix = $configData['options']['package'];
        }

        $generator->generateOrModifyYamlFile(
            LocationMapper::getOroConfigPath($srcPath, 'routing.yml'),
            __DIR__ . '/../Resources/skeleton/crud/routing.yml.tpl.php',
            [
                'resource' => $routingResource,
                'route_prefix' => $routePrefix,
                'prefix' => $prefix
            ],
            true
        );
    }
}
