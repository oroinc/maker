<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php $uses = array_merge($uses ?? [], $traits ?? [], $interfaces ?? []);sort($uses); ?>
<?= implode('' . PHP_EOL, array_map(fn ($use) => 'use ' . $use . ';', $uses)) . PHP_EOL ?>