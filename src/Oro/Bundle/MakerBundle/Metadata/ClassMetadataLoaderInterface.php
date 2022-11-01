<?php

namespace Oro\Bundle\MakerBundle\Metadata;

/**
 * Interface for Class Metadata Loaders to load metadata for a requested class.
 */
interface ClassMetadataLoaderInterface extends MetadataLoaderInterface
{
    public function getClassMetadataValue(string $entityClass, string $key): mixed;
}
