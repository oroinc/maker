services:
<?php foreach ($view_event_listeners as $event_listener => $data): ?>
    <?= $event_listener ?>:
        arguments:
            - '@request_stack'
            - '@translator'
        tags:
            - { name: kernel.event_listener, event: oro_ui.scroll_data.before.<?= $data['view_page_id'] ?>, method: onView }

<?php endforeach; ?>