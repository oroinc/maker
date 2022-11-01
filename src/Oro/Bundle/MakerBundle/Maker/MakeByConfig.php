<?php

namespace Oro\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Oro\Bundle\MakerBundle\Config\GeneratorConfiguration;
use Oro\Bundle\MakerBundle\Factory\FileManagerFactory;
use Oro\Bundle\MakerBundle\Factory\GeneratorFactory;
use Oro\Bundle\MakerBundle\Generator\GeneratorInterface;
use Oro\Bundle\MakerBundle\Helper\CrudHelper;
use Oro\Bundle\MakerBundle\Helper\OroEntityHelper;
use Oro\Bundle\MakerBundle\Metadata\MetadataStorage;
use Oro\Bundle\MakerBundle\Renderer\CodeStyleFixer;
use Oro\Bundle\MakerBundle\Util\LocationMapper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Yaml;

/**
 * Symfony Maker implementation that generates code based on provided config file.
 * Supports bundle-less and bundle aware code structures.
 */
class MakeByConfig extends AbstractMaker
{
    public function __construct(
        private GeneratorFactory $generatorFactory,
        private FileManagerFactory $fileManagerFactory,
        private OroEntityHelper $oroEntityHelper,
        private GeneratorInterface $chainGenerator,
        private CodeStyleFixer $codeStyleFixer,
        private FileManager $fileManager,
        private string $projectDir,
        iterable $metadataLoaders
    ) {
        foreach ($metadataLoaders as $metadataLoader) {
            MetadataStorage::registerMetadataLoader($metadataLoader);
        }
    }

    public static function getCommandName(): string
    {
        return 'make:by-config';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('config-path', InputArgument::REQUIRED);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $configPath = $input->getArgument('config-path');
        $srcPath = $this->projectDir . '/src';
        if (!is_dir($srcPath)) {
            throw new \RuntimeException('Unable to locate src folder ' . $srcPath);
        }
        if (!is_readable($configPath)) {
            throw new \RuntimeException('Unable to load configuration by path ' . $configPath);
        }

        $configuration = new GeneratorConfiguration();
        $configData = $configuration->processConfiguration(Yaml::parseFile($configPath));

        MetadataStorage::setGlobalMetadata('bundle_less', false);
        LocationMapper::setPackageName(CrudHelper::getBundlePrefix($configData));

        $massGenerator = $this->createBundleAwareGenerator($srcPath, $configData);
        $sourcePath = $this->getDirectoryForClass($massGenerator->getRootNamespace() . '\\Stub');

        $this->chainGenerator->generate($massGenerator, $configData, $sourcePath);
        $massGenerator->writeChanges();
        $this->codeStyleFixer->fixCodeStyles($sourcePath, $io);

        $this->writeSuccessMessage($io);
    }

    /**
     * Use own instance of generator to override namespace prefix
     */
    private function createBundleAwareGenerator(string $srcPath, array $configData): Generator
    {
        $this->fileManager = $this->fileManagerFactory->createFileManager($srcPath, __DIR__ . '/../Resources');
        $this->oroEntityHelper->setFileManager($this->fileManager);

        return $this->generatorFactory->createGenerator($this->fileManager, $configData);
    }

    private function getDirectoryForClass(string $className): string
    {
        $relativePath = dirname($this->fileManager->getRelativePathForFutureClass($className));

        return $this->fileManager->absolutizePath($relativePath);
    }
}
