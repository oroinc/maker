<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide entity_title information based on title candidates and entity fields.
 */
class TitleMetadataLoader implements ClassMetadataLoaderInterface
{
    private const TITLE_CANDIDATES = [
        'title',
        'name',
        'label',
        'subject',
        'alias',
        'code',
        'sku',
        'full_name',
        'username',
        'user_name',
        'email'
    ];

    private const CLASS_TITLE_MAPPING = [
        'Oro\Bundle\UserBundle\Entity\User' => 'fullName'
    ];

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'entity_title') {
            return null;
        }

        if (isset(self::CLASS_TITLE_MAPPING[$entityClass])) {
            return self::CLASS_TITLE_MAPPING[$entityClass];
        }

        $fields = MetadataStorage::getClassMetadata($entityClass, 'entity_fields');
        if (!$fields) {
            return null;
        }

        foreach (self::TITLE_CANDIDATES as $candidate) {
            if (array_key_exists($candidate, $fields)) {
                return $candidate;
            }
        }

        // Use first string field as title
        foreach ($fields as $name => $fieldConfig) {
            if ($fieldConfig['type'] === 'string') {
                return $name;
            }
        }

        // Use ID as title in worse case
        $idInfo = MetadataStorage::getClassMetadata($entityClass, 'id_info');
        if ($idInfo) {
            return $idInfo['field_name'];
        }

        return null;
    }
}
