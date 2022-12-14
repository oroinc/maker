<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php include __DIR__ . '/../include/php_file_start.tpl.php'; ?>

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
