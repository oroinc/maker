<?php

namespace Oro\Bundle\MakerBundle\Metadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Load select_form_type form type from entity configuration or provide configured Select2EntityType.
 */
class SelectFormTypeMetadataLoader implements ClassMetadataLoaderInterface
{
    public function __construct(
        private ConfigProvider $configProvider
    ) {
    }

    public function getClassMetadataValue(string $entityClass, string $key): mixed
    {
        if ($key !== 'select_form_type') {
            return null;
        }

        if ($this->configProvider->hasConfig($entityClass)) {
            $formType = $this->configProvider->getConfig($entityClass)->get('form_type');
            if ($formType) {
                return new TypeGuess(
                    $this->configProvider->getConfig($entityClass)->get('form_type'),
                    [],
                    Guess::HIGH_CONFIDENCE
                );
            }
        }

        $title = MetadataStorage::getClassMetadata($entityClass, 'entity_title');
        if ($title) {
            return new TypeGuess(
                'Oro\Bundle\FormBundle\Form\Type\Select2EntityType',
                [
                    'class' => $entityClass,
                    'choice_label' => Str::asLowerCamelCase($title)
                ],
                Guess::LOW_CONFIDENCE
            );
        }

        return null;
    }
}
