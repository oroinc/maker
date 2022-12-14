<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php include __DIR__ . '/../include/php_file_start.tpl.php'; ?>

/**
 * Adds new tables to the bundle.
 */
class <?= $class_name ?><?php if ($interfaces): ?> implements
<?= implode(',' . PHP_EOL, array_map(static fn ($interface) => '    ' . Str::getShortClassName($interface), $interfaces)) ?><?php endif ?><?= PHP_EOL ?>
{
<?php if ($traits): ?><?= implode(PHP_EOL, array_map(static fn ($trait) => '    use ' . Str::getShortClassName($trait) . ';', $traits)) . PHP_EOL . PHP_EOL ?><?php endif ?>
<?php include 'include/installer_extend_extension.tpl.php'; ?>

    public function up(Schema $schema, QueryBag $queries)
    {
<?php include 'include/installer_calls.tpl.php'; ?>
    }
<?php include 'include/installer_functions.tpl.php'; ?>
}
