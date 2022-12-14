<?php include __DIR__ . '/../include/php_file_start.tpl.php'; ?>

/**
 * Import-Export configuration provider for <?= $short_class_name ?>.
 */
class <?= $short_class_name ?>ImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => <?= $short_class_name ?>::class,
<?php if ($export_job_name): ?>
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => '<?= $export_job_name ?>',
<?php endif;?>
<?php if ($has_export_template): ?>
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => '<?= $alias ?>',
<?php endif;?>
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => '<?= $alias ?>',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => '<?= $alias ?>',
        ]);
    }
}
