<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;

/**
 * Load identity field information using doctrine entity metadata.
 */
class IdInfoMetadataLoader implements ClassMetadataLoaderInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'id_info') {
            return null;
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->doctrineHelper->getMetadata($entityClass);
        if (!$metadata) {
            return null;
        }
        $idFieldName = $metadata->getIdentifierFieldNames()[0];
        $idFieldType = $metadata->getTypeOfField($idFieldName);

        return [
            'field_name' => $idFieldName,
            'field_type' => $idFieldType
        ];
    }
}
