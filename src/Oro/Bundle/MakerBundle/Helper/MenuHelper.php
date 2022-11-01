<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide array suitable for yaml to generate navigation structure and shortcuts.
 */
class MenuHelper
{
    public static function getMenuConfig(array $configData): array
    {
        $appMenu = 'application_menu';
        $shortcutMenu = 'shortcuts';
        $orgTab = $configData['options']['organization'] . '_tab';
        $pkgTab = CrudHelper::getBundlePrefix($configData) . '_tab';

        $menuItems = $menuTree = $titles = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $prefix = MetadataStorage::getClassMetadata($entityName, 'prefix');
            if (!CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }
            $entityAlias = Str::asSnakeCase($entityName);
            $alias = $prefix . '_list';
            $routes = CrudHelper::getRouteNames($entityName);

            $menuItems[$alias] = [
                'label' => TranslationHelper::getEntityPluralLabel($entityName),
                'route' => $routes['index'],
                'extras' => ['routes' => array_values($routes)]
            ];
            $menuTree[$appMenu]['children'][$orgTab]['children'][$pkgTab]['children'][$alias] = null;

            $titles[$routes['index']] = null;
            $titles[$routes['create']] = 'oro.ui.create_entity';
            $titles[$routes['view']] = '%title% - oro.ui.view';
            $titles[$routes['update']] = '%title% - oro.ui.edit';

            $shortcutCreate = 'shortcut_' . $routes['create'];
            $menuItems[$shortcutCreate] = [
                'label' => TranslationHelper::getMenuCreateShortcutLabel($configData, $entityAlias),
                'route' => $routes['create']
            ];
            $menuTree[$shortcutMenu]['children'][$shortcutCreate] = null;

            $shortcutList = 'shortcut_' . $routes['index'];
            $menuItems[$shortcutList] = [
                'label' => TranslationHelper::getMenuListShortcutLabel($configData, $entityAlias),
                'route' => $routes['index']
            ];
            $menuTree[$shortcutMenu]['children'][$shortcutList] = null;
        }

        if ($menuItems || $menuTree || $titles) {
            $menuItems[$orgTab] = [
                'label' => TranslationHelper::getOrganizationLabel($configData),
                'uri' => '#',
                'extras' => ['icon' => 'fa-question']
            ];
            $menuItems[$pkgTab] = [
                'label' => TranslationHelper::getPackageLabel($configData),
                'uri' => '#'
            ];

            return [
                'navigation' => [
                    'menu_config' => [
                        'items' => $menuItems,
                        'tree' => $menuTree
                    ],
                    'titles' => $titles
                ]
            ];
        }

        return [];
    }
}
