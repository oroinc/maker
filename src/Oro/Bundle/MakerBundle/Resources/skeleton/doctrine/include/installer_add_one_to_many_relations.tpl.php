<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['one_to_many'])): ?>

    private function add<?= Str::asCamelCase($table_name) ?>OneToManyRelations(Schema $schema): void
    {
<?php foreach ($table_config['one_to_many'] as $field_name => $relation_options): ?>
        $this->extendExtension->addManyToOneRelation(
            $schema,
            '<?= $relation_options['target_table_name'] ?>', // Owning table name
            '<?= $table_name ?>', // Field Name
            '<?= $table_name ?>', // Relation table name
            '<?= $relation_options['title_column'] ?>',
            <?= $relation_options['options'] . PHP_EOL ?>
        );
<?php endforeach; ?>
    }
<?php endif;endforeach; ?>