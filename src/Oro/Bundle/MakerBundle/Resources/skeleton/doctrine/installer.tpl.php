<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php $uses = array_merge($uses, $traits, $interfaces);sort($uses); ?>
<?= implode('' . PHP_EOL, array_map(fn ($use) => 'use ' . $use . ';', $uses)) . PHP_EOL ?>

/**
 * Creates all tables required for the bundle.
 */
class <?= $class_name ?><?php if ($interfaces): ?> implements
<?= implode(',' . PHP_EOL, array_map(static fn ($interface) => '    ' . Str::getShortClassName($interface), $interfaces)) ?><?php endif ?><?= PHP_EOL ?>
{
<?php if ($traits): ?><?= implode(PHP_EOL, array_map(static fn ($trait) => '    use ' . Str::getShortClassName($trait) . ';', $traits)) . PHP_EOL . PHP_EOL ?><?php endif ?>
<?php if ($requires_extend_extension): ?>
    protected ExtendExtension $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
<?php endif; ?>

    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    public function up(Schema $schema, QueryBag $queries)
    {
<?php foreach (array_keys($tables_config) as $table_name): ?>
        $this->create<?= Str::asCamelCase($table_name) ?>Table($schema);
<?php endforeach; ?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['relations'])): ?>
        $this->add<?= Str::asCamelCase($table_name) ?>ForeignKeys($schema);
<?php endif;endforeach; ?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['enums'])): ?>
        $this->add<?= Str::asCamelCase($table_name) ?>Enums($schema);
<?php endif;endforeach; ?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['images'])): ?>
        $this->add<?= Str::asCamelCase($table_name) ?>Images($schema);
<?php endif;endforeach; ?>
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['one_to_many'])): ?>
        $this->add<?= Str::asCamelCase($table_name) ?>OneToManyRelations($schema);
<?php endif;endforeach; ?>
    }
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
<?php foreach ($tables_config as $table_name => $table_config): if (!empty($table_config['images'])): ?>

    private function add<?= Str::asCamelCase($table_name) ?>Images(Schema $schema): void
    {
<?php foreach ($table_config['images'] as $image_field => $image_options): ?>
        $this->attachmentExtension->addImageRelation(
            $schema,
            '<?= $table_name ?>',
            '<?= $image_field ?>',
            ['importexport' => ['excluded' => true]]
        );
<?php endforeach; ?>
    }
<?php endif;endforeach; ?>
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
}
