<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;

/**
 * Load table_name from doctrine entity metadata.
 */
class TableNameMetadataLoader implements ClassMetadataLoaderInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'table_name') {
            return null;
        }

        $metadata = $this->doctrineHelper->getMetadata($entityClass);
        if (!$metadata instanceof ClassMetadataInfo) {
            return null;
        }

        return $metadata->getTableName();
    }
}
