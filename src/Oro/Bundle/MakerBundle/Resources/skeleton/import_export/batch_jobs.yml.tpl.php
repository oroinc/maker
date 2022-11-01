connector:
    name: oro_importexport
    jobs:
<?php foreach ($data as $job_name => $config): ?>
        <?= $job_name ?>:
            title: "<?= $job_name ?>"
            type: export
            steps:
                export:
                    title:     export
                    class:     Oro\Bundle\BatchBundle\Step\ItemStep
                    services:
                        reader: <?= $prefix ?>.importexport.reader.<?= $config['suffix'] ?><?= PHP_EOL ?>
                        processor: oro_importexport.processor.export_delegate
                        writer: oro_importexport.writer.csv
                    parameters: ~
<?php endforeach; ?>
