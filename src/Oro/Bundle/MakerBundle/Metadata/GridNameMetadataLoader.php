<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Load grid_name from grid or form scope of entity configuration.
 */
class GridNameMetadataLoader implements ClassMetadataLoaderInterface
{
    private const CLASS_GRID_MAPPING = [
        // 'stdClass' => 'grid-name'
    ];

    public function __construct(
        private ConfigProvider $gridConfigProvider,
        private ConfigProvider $formConfigProvider
    ) {
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'grid_name') {
            return null;
        }

        $gridName = self::CLASS_GRID_MAPPING[$entityClass] ?? null;
        if (!$gridName && $this->gridConfigProvider->hasConfig($entityClass)) {
            $gridName = $this->gridConfigProvider->getConfig($entityClass)->get('default');
        }
        if (!$gridName && $this->formConfigProvider->hasConfig($entityClass)) {
            $gridName = $this->formConfigProvider->getConfig($entityClass)->get('grid_name');
        }

        return $gridName;
    }
}
