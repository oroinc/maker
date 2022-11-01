<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php $uses = array_merge($uses, $traits, $interfaces);sort($uses); ?>
<?= implode('' . PHP_EOL, array_map(fn ($use) => 'use ' . $use . ';', $uses)) . PHP_EOL ?>

/**
 * ORM Entity <?= $entity_short_name ?>.
 *
<?= implode('', $entity_annotations) ?>
 */
class <?= $class_name ?> extends <?= $extend_entity_class_name ?><?php if ($interfaces): ?> implements
<?= implode(',' . PHP_EOL, array_map(static fn ($interface) => '    ' . Str::getShortClassName($interface), $interfaces)) ?><?php endif ?><?= PHP_EOL ?>
{
<?php if ($traits): ?><?= implode(PHP_EOL, array_map(static fn ($trait) => '    use ' . Str::getShortClassName($trait) . ';', $traits)) . PHP_EOL . PHP_EOL ?><?php endif ?>
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
