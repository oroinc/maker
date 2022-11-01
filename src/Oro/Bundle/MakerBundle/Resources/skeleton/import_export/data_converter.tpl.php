<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

/**
 * Import-Export data converter for <?= $short_class_name ?>.
 * Adds holder entity id for each imported record and removes it from exported rows.
 */
class <?= $short_class_name ?>DataConverter extends ConfigurableTableDataConverter implements ContextAwareInterface
{
    private ContextInterface $context;

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if ($this->context && empty($importedRecord['<?= $relation_owner_field ?>_id'])) {
            $importedRecord['<?= $relation_owner_field . ':id' ?>'] = (int)$this->context->getOption('holder_entity_id');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        unset($exportedRecord['<?= $relation_owner_field ?>']);

        return parent::convertToExportFormat($exportedRecord, $skipNullValues);
    }

    protected function getBackendHeader()
    {
        return array_filter(
            parent::getBackendHeader(),
            static fn (string $header) => !str_starts_with($header, '<?= $relation_owner_field ?>:')
        );
    }
}
