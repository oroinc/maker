<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['enums'])): ?>

    private function add<?= Str::asCamelCase($table_name) ?>Enums(Schema $schema): void
    {
<?php foreach ($table_config['enums'] as $enum_name => $enum_options): ?>
        $this->extendExtension->addEnumField(
            $schema,
            '<?= $table_name ?>',
            '<?= $enum_name ?>',
            '<?= $enum_options['enum_code'] ?>',
            <?= $enum_options['is_multiple'] ? 'true' : 'false' ?>,
            false,
            <?= $enum_options['options'] . PHP_EOL ?>
        );
<?php endforeach; ?>
    }
<?php endif;endforeach; ?>