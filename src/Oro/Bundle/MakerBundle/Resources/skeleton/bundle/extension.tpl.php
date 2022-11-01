<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class <?= $class_name; ?> extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
<?php foreach ($config_files as $config_file) : ?>
        $loader->load('<?= $config_file; ?>');<?= PHP_EOL; ?>
<?php endforeach; ?>
    }
}
