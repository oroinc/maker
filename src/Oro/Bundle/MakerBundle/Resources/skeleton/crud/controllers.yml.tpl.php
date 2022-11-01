services:
    _defaults:
        public: true
<?php foreach ($controllers as $controller): ?>

    <?= $controller ?>:
        calls:
            - ['setContainer', ['@Psr\Container\ContainerInterface']]
        tags:
            - { name: container.service_subscriber }
<?php endforeach; ?>