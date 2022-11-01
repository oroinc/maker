<?php

namespace Oro\Bundle\MakerBundle\Helper;

use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;

/**
 * Helper used for early configuration of CreateOrSelectFormType.
 */
class FormHelper
{
    public static function configureCreateOrSelectFormTypeClassDetails(
        Generator $generator,
        string $entityName
    ): void {
        $selectFormTypeClassNameDetails = MetadataStorage::getClassMetadata(
            $entityName,
            'select_form_type_class_name_details'
        );
        // Nothing to do, already called
        if ($selectFormTypeClassNameDetails) {
            return;
        }

        $selectFormTypeClassNameDetails = $generator->createClassNameDetails(
            Str::asCamelCase($entityName) . 'CreateOrSelect',
            'Form\\Type',
            'Type'
        );
        MetadataStorage::addClassMetadata(
            $entityName,
            'select_form_type_class_name_details',
            $selectFormTypeClassNameDetails
        );
    }
}
