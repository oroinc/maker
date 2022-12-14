<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Bundle\MakerBundle\Helper\CrudHelper;
use Oro\Bundle\MakerBundle\Helper\FormHelper;
use Oro\Bundle\MakerBundle\Helper\GridHelper;
use Oro\Bundle\MakerBundle\Helper\TplHelper;
use Oro\Bundle\MakerBundle\Helper\TranslationHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Generates form type and Create Or Select Form Types for entities.
 */
class FormGenerator implements GeneratorInterface
{
    public function generate(Generator $generator, array &$configData, string $srcPath): bool
    {
        $formTypes = [];
        $autocompleteHandlers = [];
        foreach ($configData['entities'] as $entityName => $entityConfig) {
            if (!CrudHelper::isCrudEnabled($entityConfig)) {
                continue;
            }

            $handlerData = $this->generateCreateOrSelectFormType(
                $generator,
                $entityName,
                $configData
            );
            if ($handlerData) {
                $formTypes[] = $handlerData[0];
                $autocompleteHandlers[] = $handlerData[1];
            }
        }

        foreach ($configData['entities'] as $entityName => $entityConfig) {
            $formTypes[] = $this->generateEntityFormType($generator, $entityName, $entityConfig);
        }

        $generator->generateOrModifyYamlFile(
            LocationMapper::getServicesConfigPath($srcPath, 'form_types.yml'),
            __DIR__ . '/../Resources/skeleton/form/form_types.yml.tpl.php',
            [
                'form_types' => $formTypes,
                'autocomplete_handlers' => $autocompleteHandlers,
                'prefix' => CrudHelper::getBundlePrefix($configData)
            ]
        );

        MetadataStorage::appendGlobalMetadata('service_config_files', 'form_types.yml');

        return true;
    }

    protected function generateEntityFormType(
        Generator $generator,
        string $entityName,
        array $entityConfig
    ): string {
        $shortName = Str::asCamelCase($entityName);
        if (str_ends_with(strtolower($shortName), 'type')) {
            $shortName .= 'Form';
        }

        $formTypeClassNameDetails = $generator->createClassNameDetails(
            $shortName,
            'Form\\Type',
            'Type'
        );

        $entityClass = MetadataStorage::getClassName($entityName);
        $uses = [
            'Symfony\Component\Form\AbstractType',
            'Symfony\Component\Form\FormBuilderInterface',
            'Symfony\Component\OptionsResolver\OptionsResolver',
            $entityClass
        ];
        $generator->generateClass(
            $formTypeClassNameDetails->getFullName(),
            __DIR__ . '/../Resources/skeleton/form/form_type.tpl.php',
            [
                'entity_class_name' => $entityClass,
                'form_fields' => $this->getFields(
                    $entityName,
                    $entityConfig,
                    $uses
                ),
                'uses' => $uses
            ]
        );
        MetadataStorage::addClassMetadata($entityName, 'form_type', $formTypeClassNameDetails->getFullName());

        return $formTypeClassNameDetails->getFullName();
    }

    protected function generateCreateOrSelectFormType(
        Generator $generator,
        string $entityName,
        array $configData
    ): ?array {
        FormHelper::configureCreateOrSelectFormTypeClassDetails($generator, $entityName);
        $selectFormTypeClassNameDetails = MetadataStorage::getClassMetadata(
            $entityName,
            'select_form_type_class_name_details'
        );

        if (!$selectFormTypeClassNameDetails) {
            return null;
        }

        $title = MetadataStorage::getClassMetadata($entityName, 'entity_title');
        if (!$title) {
            return null;
        }
        MetadataStorage::addClassMetadata(
            $entityName,
            'select_form_type',
            new TypeGuess($selectFormTypeClassNameDetails->getFullName(), [], TypeGuess::HIGH_CONFIDENCE)
        );

        $entityClass = MetadataStorage::getClassName($entityName);
        $autocompleteAlias = MetadataStorage::getClassMetadata($entityName, 'prefix');
        $routes = CrudHelper::getRouteNames($entityName);
        $generator->generateClass(
            $selectFormTypeClassNameDetails->getFullName(),
            __DIR__ . '/../Resources/skeleton/form/create_or_select_form_type.tpl.php',
            [
                'entity_class_name' => $entityClass,
                'autocomplete_alias' => $autocompleteAlias,
                'routes' => $routes,
                'select_grid_name' => GridHelper::getSelectGridName($entityName)
            ]
        );
        $org = Str::asSnakeCase($configData['options']['organization']);
        $pkg = Str::asSnakeCase($configData['options']['package']);

        return [
            $selectFormTypeClassNameDetails->getFullName(),
            [
                'service_name' => sprintf('%s.%s.form.autocomplete.%s.search_handler', $org, $pkg, $entityName),
                'entity_class' => $entityClass,
                'entity_title' => Str::asLowerCamelCase($title),
                'alias' => $autocompleteAlias,
                'acl_resource' => $routes['view']
            ]
        ];
    }

    protected function getFields(
        string $entityAlias,
        array $entityConfig,
        array &$uses
    ): array {
        $formFields = [];
        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            $guess = $this->guessType($entityAlias, $fieldName, $fieldConfig);

            if (!$guess) {
                continue;
            }

            $type = $guess->getType();
            $options = $guess->getOptions();
            $options['label'] = TranslationHelper::getFieldLabel($entityAlias, $fieldName);
            $options['required'] = !empty($fieldConfig['required']);

            if (!in_array($type, $uses, true)) {
                $uses[] = $type;
            }

            $formFields[Str::asLowerCamelCase($fieldName)] = [
                'type' => Str::getShortClassName($type),
                'options' => TplHelper::dumpArray($options)
            ];
        }

        return $formFields;
    }

    protected function guessType(
        string $entityName,
        string $fieldName,
        array $fieldConfig
    ): ?TypeGuess {
        if ($fieldConfig['type'] === 'relation') {
            if ($fieldConfig['relation_type'] === 'many-to-one') {
                $selectTypeGuess = MetadataStorage::getClassMetadata(
                    $fieldConfig['relation_target'],
                    'select_form_type'
                );
                if ($selectTypeGuess) {
                    return $selectTypeGuess;
                }
            }

            return null;
        }

        return $this->guessScalarType($entityName, $fieldName, $fieldConfig);
    }

    protected function guessScalarType(string $entityName, string $fieldName, array $fieldConfig)
    {
        $type = $fieldConfig['type'];
        if (str_contains($fieldName, 'email')) {
            $type = 'email';
        }
        $options = [];

        switch ($type) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\IntegerType';
                break;

            case 'float':
            case 'decimal':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\NumberType';
                break;

            case 'boolean':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\CheckboxType';
                break;

            case 'date':
                $formType = 'Oro\Bundle\FormBundle\Form\Type\OroDateType';
                break;

            case 'time':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\TimeType';
                $options = ['model_timezone' => 'UTC', 'view_timezone' => 'UTC'];
                break;

            case 'datetime':
                $formType = 'Oro\Bundle\FormBundle\Form\Type\OroDateTimeType';
                break;

            case 'text':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
                break;

            case 'percent':
                $formType = 'Oro\Bundle\FormBundle\Form\Type\OroPercentType';
                break;

            case 'html':
                $formType = 'Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType';
                break;

            case 'wysiwyg':
                $formType = 'Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType';
                break;

            case 'image':
                $formType = 'Oro\Bundle\AttachmentBundle\Form\Type\ImageType';
                break;

            case 'email':
                $formType = 'Symfony\Component\Form\Extension\Core\Type\EmailType';
                break;

            case 'enum':
            case 'enum[]':
                $formType = 'Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType';
                $options = ['enum_code' => MetadataStorage::getFieldMetadata($entityName, $fieldName, 'enum_code')];
                break;

            default:
                $formType = 'Symfony\Component\Form\Extension\Core\Type\TextType';
        }

        return new TypeGuess($formType, $options, Guess::HIGH_CONFIDENCE);
    }
}
