<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Load entity fields by doctrine metadata.
 */
class EntityFieldsMetadataLoader implements ClassMetadataLoaderInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'entity_fields') {
            return null;
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->doctrineHelper->getMetadata($entityClass);
        if (!$metadata instanceof ClassMetadata) {
            return null;
        }

        $fieldNames = $metadata->getFieldNames();
        $entityFields = [];
        foreach ($fieldNames as $fieldName) {
            $entityFields[Str::asSnakeCase($fieldName)] = [
                'type' => $metadata->getTypeOfField($fieldName)
            ];
        }

        return $entityFields;
    }
}
