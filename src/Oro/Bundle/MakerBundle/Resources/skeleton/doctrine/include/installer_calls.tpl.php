<?php

use Symfony\Bundle\MakerBundle\Str;

?>
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