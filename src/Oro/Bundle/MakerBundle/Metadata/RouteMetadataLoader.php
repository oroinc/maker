<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Load routing metadata from entity configuration.
 */
class RouteMetadataLoader implements ClassMetadataLoaderInterface
{
    public function __construct(
        private ConfigManager $configManager
    ) {
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if (!str_starts_with($key, 'route_')) {
            return null;
        }

        if (!$this->configManager->hasConfig($entityClass)) {
            return null;
        }

        $routeType = str_replace('route_', '', $key);
        if ($routeType === 'index') {
            $routeType = 'name';
        }

        try {
            return $this->configManager->getEntityMetadata($entityClass)->getRoute($routeType);
        } catch (\Exception $e) {
            try {
                if ($routeType === 'name') {
                    return null;
                }
                $indexRoute = $this->configManager->getEntityMetadata($entityClass)->getRoute('name');

                return preg_replace('/_index$/', '_' . $routeType, $indexRoute);
            } catch (\Exception $e) {
            }
        }

        return null;
    }
}
