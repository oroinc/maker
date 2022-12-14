<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['relations'])): ?>

    private function add<?= Str::asCamelCase($table_name) ?>ForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('<?= $table_name ?>');
<?php foreach ($table_config['relations'] as $field_name => $field_config): ?>
        $table->addForeignKeyConstraint(
            $schema->getTable('<?= $field_config['target_table_name'] ?>'),
            ['<?= $field_name ?>'],
            ['<?= $field_config['target_pk_field_name'] ?>'],
            ['onUpdate' => null, 'onDelete' => '<?= $field_config['on_delete'] ?>']
        );
<?php endforeach; ?>
    }
<?php endif;endforeach; ?>