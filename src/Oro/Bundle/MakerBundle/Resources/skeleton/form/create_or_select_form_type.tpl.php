<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for <?= Str::getShortClassName($entity_class_name) ?> entity with inline create & select buttons
 */
class <?= $class_name ?> extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => '<?= $autocomplete_alias ?>',
                'grid_name' => '<?= $select_grid_name ?>',
<?php if ($create_enabled): ?>
                'create_form_route' => '<?= $routes['create'] ?>',
<?php else: ?>
                'create_enabled' => false,
<?php endif; ?>
            ]
        );
    }

    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
