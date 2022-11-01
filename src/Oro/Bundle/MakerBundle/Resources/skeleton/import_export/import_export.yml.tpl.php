services:
<?php foreach ($data as $config): ?>
    # <?= $config['short_class_name'] ?> Import-Export services
<?php if ($config['reader_class_name']): ?>
    <?= $prefix ?>.importexport.reader.<?= $config['suffix'] ?>:
        class: '<?= $config['reader_class_name'] ?>'
        parent: oro_importexport.reader.entity

<?php endif ?>
    <?= $prefix ?>.importexport.data_converter.<?= $config['suffix'] ?>:
<?php if ($config['data_converter_class_name']): ?>
        class: '<?= $config['data_converter_class_name'] ?>'
<?php endif ?>
        parent: oro_importexport.data_converter.configurable

    <?= $prefix ?>.importexport.strategy.<?= $config['suffix'] ?>.add_or_replace:
        parent: oro_importexport.strategy.configurable_add_or_replace

    <?= $prefix ?>.importexport.processor.import.<?= $config['suffix'] ?>:
        parent: oro_importexport.processor.import_abstract
        calls:
            - [ setDataConverter, [ '@<?= $prefix ?>.importexport.data_converter.<?= $config['suffix'] ?>' ] ]
            - [ setStrategy, [ '@<?= $prefix ?>.importexport.strategy.<?= $config['suffix'] ?>.add_or_replace' ] ]
        tags:
            -   name: oro_importexport.processor
                type: import
                entity: '<?= $config['class_name'] ?>'
                alias: <?= $config['alias'] . PHP_EOL ?>
            -   name: oro_importexport.processor
                type: import_validation
                entity: '<?= $config['class_name'] ?>'
                alias: <?= $config['alias'] . PHP_EOL ?>

    <?= $prefix ?>.importexport.processor.export.<?= $config['suffix'] ?>:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [ setDataConverter, [ '@<?= $prefix ?>.importexport.data_converter.<?= $config['suffix'] ?>' ] ]
        tags:
            -   name: oro_importexport.processor
                type: export
                entity: '<?= $config['class_name'] ?>'
                alias: <?= $config['alias'] . PHP_EOL ?>

    <?= $prefix ?>.importexport.configuration_provider.<?= $config['suffix'] ?>:
        class: '<?= $config['config_class_name'] ?>'
        tags:
            - { name: oro_importexport.configuration, alias: <?= $config['alias'] ?> }
<?php endforeach; ?>