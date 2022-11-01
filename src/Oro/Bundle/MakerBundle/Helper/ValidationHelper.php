<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Provide array suitable for yaml to generate validation rules.
 */
class ValidationHelper
{
    public static function getValidatorConfiguration(array $configData): array
    {
        $validation = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $className = MetadataStorage::getClassName($entityName);
            foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
                $entityField = Str::asLowerCamelCase($fieldName);
                self::addNotBlankConstraint($validation, $fieldConfig, $className, $entityField);
                self::addLengthConstraint($validation, $fieldConfig, $className, $entityField);
            }
            $inverseRelations = MetadataStorage::getClassMetadata($entityName, 'inverse_many_to_one', []);
            foreach ($inverseRelations as $fieldName => $fieldConfig) {
                $entityField = Str::asLowerCamelCase($fieldName);
                self::addNotBlankConstraint($validation, $fieldConfig, $className, $entityField);
            }
        }

        return $validation;
    }

    private static function addNotBlankConstraint(
        array &$validation,
        array $fieldConfig,
        string $className,
        string $fieldName
    ): void {
        if (empty($fieldConfig['required'])) {
            return;
        }
        if ($fieldConfig['type'] === 'boolean') {
            $validation[$className]['properties'][$fieldName][] = ['NotNull' => null];
        } else {
            $validation[$className]['properties'][$fieldName][] = ['NotBlank' => null];
        }
    }

    private static function addLengthConstraint(
        array &$validation,
        array $fieldConfig,
        string $className,
        string $fieldName
    ): void {
        if (!isset($fieldConfig['max_length']) && !isset($fieldConfig['min_length'])) {
            return;
        }

        $constraintOptions = [];
        if (!empty($fieldConfig['required'])) {
            $constraintOptions['min'] = 1;
        }
        if (isset($fieldConfig['min_length'])) {
            $constraintOptions['min'] = $fieldConfig['min_length'];
        }
        if (isset($fieldConfig['max_length'])) {
            $constraintOptions['max'] = $fieldConfig['max_length'];
        }
        $constraintOptions['allowEmptyString'] = empty($fieldConfig['required']);

        $validation[$className]['properties'][$fieldName][] = ['Length' => $constraintOptions];
    }
}
