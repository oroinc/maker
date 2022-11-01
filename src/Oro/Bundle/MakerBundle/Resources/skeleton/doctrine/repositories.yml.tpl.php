services:
<?php foreach ($repositories as $repository): ?>

    <?= $repository ?>:
        parent: oro_entity.abstract_repository
        tags:
            - { name: doctrine.repository_service }
<?php endforeach; ?>