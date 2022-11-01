<?php

use Symfony\Bundle\MakerBundle\Str;

?>
services:
<?php foreach ($form_types as $form_type): ?>
<?php $alias = $prefix . '_' . Str::asSnakeCase(Str::getShortClassName($form_type)); ?>
    <?= $form_type ?>:
        tags:
            - { name: form.type, alias: <?= $alias ?> }

<?php endforeach; ?>
<?php foreach ($autocomplete_handlers as $autocomplete_handler): ?>
    <?= $autocomplete_handler['service_name'] ?>:
        parent: oro_form.autocomplete.search_handler
        arguments:
            - '<?= $autocomplete_handler['entity_class'] ?>'
            - ['<?= $autocomplete_handler['entity_title'] ?>']
        tags:
            - { name: oro_form.autocomplete.search_handler, alias: <?= $autocomplete_handler['alias'] ?>, acl_resource: <?= $autocomplete_handler['acl_resource'] ?> }

<?php endforeach; ?>