<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

/**
 * Load values for <?= $enum_code ?> enum.
 */
class <?= $class_name ?> extends AbstractEnumFixture
{
    protected function getData()
    {
        return [
<?php foreach ($values as $key => $value): ?>
            '<?= $key ?>' => '<?= $value ?>',
<?php endforeach; ?>
        ];
    }

    protected function getEnumCode()
    {
        return '<?= $enum_code ?>';
    }

    protected function getDefaultValue()
    {
        return <?php if ($default_value !== null): ?>'<?= $default_value ?>'<?php else: ?>null<?php endif; ?>;
    }
}
