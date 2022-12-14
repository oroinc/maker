<?php foreach ($config_files as $config_file) : ?>
        $loader->load('<?= $config_file; ?>');<?= PHP_EOL; ?>
<?php endforeach; ?>