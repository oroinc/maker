<?php

namespace Oro\Bundle\MakerBundle\Metadata;

/**
 * Interface for Field Metadata Loaders to load metadata for a requested class and field.
 */
interface FieldMetadataLoaderInterface extends MetadataLoaderInterface
{
    public function getFieldMetadataValue(string $entityClass, string $fieldName, string $key): mixed;
}
