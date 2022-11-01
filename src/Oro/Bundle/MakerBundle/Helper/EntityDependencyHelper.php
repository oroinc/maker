<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Configure uses, traits and interfaces for an entity
 *
 *  - add ownership related dependencies
 *  - add name aware interfaces based on available fields
 *  - add email aware interface based on available fields
 *  - add website aware interface based on available fields
 *
 *  @SuppressWarnings(PHPMD)
 */
class EntityDependencyHelper
{
    public static function configureTraitsAndInterfaces(
        string $entityName,
        array $entityConfig,
        array &$traits,
        array &$interfaces
    ): void {
        $traits[] = 'Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait';
        $interfaces[] = 'Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface';

        self::addOwnershipDependencies($entityConfig, $traits, $interfaces);
        self::addFrontendOwnershipDependencies($entityConfig, $traits, $interfaces);
        self::addNameAwareInterfaces($entityName, $entityConfig);
        self::addEmailInterfaces($entityName, $entityConfig);
        self::addWebsiteAwareInterface($entityName, $entityConfig);
    }

    public static function getInverseFieldName(string $entityName, string $fieldName): string
    {
        return Str::asSnakeCase(Str::pluralCamelCaseToSingular(Str::asCamelCase($fieldName)))
            . '_'
            . Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase(Str::asCamelCase($entityName)));
    }

    protected static function addOwnershipDependencies(array $entityConfig, array &$traits, array &$interfaces): void
    {
        $prefix = '';
        if (!empty($entityConfig['configuration']['auditable'])) {
            $prefix = 'Auditable';
        }

        $ownership = $entityConfig['configuration']['owner'] ?? null;
        if ($ownership === 'user') {
            $traits[] = sprintf(
                'Oro\Bundle\UserBundle\Entity\Ownership\%sUserAwareTrait',
                $prefix
            );
        } elseif ($ownership === 'business_unit') {
            $traits[] = sprintf('Oro\Bundle\OrganizationBundle\Entity\Ownership\%sBusinessUnitAwareTrait', $prefix);
        } elseif ($ownership === 'organization') {
            $traits[] = sprintf('Oro\Bundle\OrganizationBundle\Entity\Ownership\%sOrganizationAwareTrait', $prefix);
        }

        if ($ownership) {
            $interfaces[] = 'Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface';
        }
    }

    protected static function addFrontendOwnershipDependencies(
        array $entityConfig,
        array &$traits,
        array &$interfaces
    ): void {
        $prefix = '';
        if (!empty($entityConfig['configuration']['auditable'])) {
            $prefix = 'Auditable';
        }

        $ownership = $entityConfig['configuration']['frontend_owner'] ?? null;
        if ($ownership === 'customer_user') {
            $traits[] = sprintf(
                'Oro\Bundle\CustomerBundle\Entity\Ownership\%sFrontendCustomerUserAwareTrait',
                $prefix
            );
            $interfaces[] = 'Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface';
        } elseif ($ownership === 'customer') {
            $traits[] = sprintf('Oro\Bundle\CustomerBundle\Entity\Ownership\%sFrontendCustomerAwareTrait', $prefix);
            $interfaces[] = 'Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface';
        }
    }

    protected static function addNameAwareInterfaces(string $entityName, array $entityConfig): void
    {
        $nameInterfaces = [];
        $postAddInterfaces = [];
        foreach (array_keys($entityConfig['fields']) as $fieldName) {
            switch (Str::asSnakeCase($fieldName)) {
                case 'name_prefix':
                    $nameInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\NamePrefixInterface';
                    break;
                case 'first_name':
                    $nameInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\FirstNameInterface';
                    break;
                case 'middle_name':
                    $nameInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\MiddleNameInterface';
                    break;
                case 'last_name':
                    $nameInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\LastNameInterface';
                    break;
                case 'name_suffix':
                    $nameInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\NameSuffixInterface';
                    break;
                case 'name':
                    $postAddInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\NameInterface';
                    break;
            }
        }

        if (count($nameInterfaces) === 5) {
            $postAddInterfaces[] = 'Oro\Bundle\LocaleBundle\Model\FullNameInterface';
        } else {
            $postAddInterfaces = array_merge($postAddInterfaces, $nameInterfaces);
        }

        if ($postAddInterfaces) {
            MetadataStorage::addClassMetadata($entityName, 'post_add_interfaces', $postAddInterfaces);
        }
    }

    protected static function addEmailInterfaces(string $entityName, array $entityConfig): void
    {
        if (array_key_exists('email', $entityConfig['fields'])) {
            MetadataStorage::appendArrayClassMetadata(
                $entityName,
                'post_add_interfaces',
                'Oro\Bundle\EmailBundle\Model\EmailHolderInterface'
            );
        }
    }

    protected static function addWebsiteAwareInterface(string $entityName, array $entityConfig): void
    {
        if (array_key_exists('website', $entityConfig['fields'])
            && !empty($entityConfig['fields']['website']['relation_target'])
            && $entityConfig['fields']['website']['relation_target'] === 'Oro\Bundle\WebsiteBundle\Entity\Website'
        ) {
            MetadataStorage::appendArrayClassMetadata(
                $entityName,
                'post_add_interfaces',
                'Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface'
            );
        }
    }
}
