<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php foreach ($tables_config as $table_name => $table_config): ?>

    private function create<?= Str::asCamelCase($table_name) ?>Table(Schema $schema): void
    {
        $table = $schema->createTable('<?= $table_name ?>');
<?php if (!empty($table_config['pk'])): foreach ($table_config['pk'] as $field_name => $field_config): if (empty($table_config['fields'][$field_name]) && empty($table_config['relations'][$field_name])): ?>
        $table->addColumn('<?= $field_name ?>', '<?= $field_config['type'] ?>'<?php if (!empty($field_config['options'])): ?>, <?= $field_config['options'] ?><?php endif; ?>);
<?php endif;endforeach;endif; ?>
<?php if (!empty($table_config['fields'])): foreach ($table_config['fields'] as $field_name => $field_config): ?>
        $table->addColumn('<?= $field_name ?>', '<?= $field_config['type'] ?>'<?php if (!empty($field_config['options'])): ?>, <?= $field_config['options'] ?><?php endif; ?>);
<?php endforeach;endif; ?>
<?php if (!empty($table_config['relations'])): foreach ($table_config['relations'] as $field_name => $field_config): ?>
        $table->addColumn('<?= $field_name ?>', '<?= $field_config['target_pk_type'] ?>'<?php if (!empty($field_config['options'])): ?>, <?= $field_config['options'] ?><?php endif; ?>);
<?php endforeach;endif; ?>
<?php if (!empty($table_config['pk'])): ?>
<?php $pks = implode(', ', array_map(static fn ($pk) => "'" . $pk . "'", array_keys($table_config['pk']))); ?>
        $table->setPrimaryKey([<?= $pks ?>]);
<?php endif; ?>
    }
<?php endforeach; ?>