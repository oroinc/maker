services:
<?php foreach ($controllers as $controller): ?>

    <?= $controller ?>:
        public: true
        calls:
            - ['setContainer', ['@Psr\Container\ContainerInterface']]
        tags:
            - { name: container.service_subscriber }
<?php endforeach; ?>