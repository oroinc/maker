<?php

use Symfony\Bundle\MakerBundle\Str;

?>
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