<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php include __DIR__ . '/../include/php_file_start.tpl.php'; ?>

/**
 * Form type for <?= Str::getShortClassName($entity_class_name) ?> entity.
 */
class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
            ->add(
                '<?= $form_field ?>',
                <?= $typeOptions['type'].'::class' ?>,
                <?= $typeOptions['options'] . PHP_EOL ?>
            )
<?php endforeach; ?>
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => <?= Str::getShortClassName($entity_class_name) ?>::class
        ]);
    }
}
