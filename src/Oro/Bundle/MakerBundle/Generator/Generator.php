<?php

namespace Oro\Bundle\MakerBundle\Generator;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator as BaseGenerator;
use Symfony\Bundle\MakerBundle\GeneratorTwigHelper;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Yaml\Yaml;

class Generator extends BaseGenerator
{
    private FileManager $fileManager;
    private GeneratorTwigHelper $twigHelper;

    public function __construct(
        FileManager $fileManager,
        string $namespacePrefix,
        PhpCompatUtil $phpCompatUtil = null,
        TemplateComponentGenerator $templateComponentGenerator = null
    ) {
        $this->fileManager = $fileManager;
        $this->twigHelper = new GeneratorTwigHelper($fileManager);

        parent::__construct($fileManager, $namespacePrefix, $phpCompatUtil, $templateComponentGenerator);
    }

    public function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    public function generateOrModifyYamlFile(
        string $targetPath,
        string $templateName,
        array $variables = [],
        bool $forceArrayMerge = false
    ): void {
        $variables = array_merge($variables, [
            'helper' => $this->twigHelper,
        ]);
        $variables['relative_path'] = $this->fileManager->relativizePath($targetPath);
        $contents = $this->fileManager->parseTemplate($templateName, $variables);

        if ($this->fileManager->fileExists($targetPath)) {
            $contents = $this->mergeYamls(
                $this->fileManager->getFileContents($targetPath),
                $contents,
                Yaml::parse($contents),
                4,
                $forceArrayMerge
            );
        }

        $this->dumpYaml($targetPath, $contents);
    }

    public function addOrModifyYamlFile(
        string $targetPath,
        array $data,
        int $inline = 4,
        bool $forceArrayMerge = false
    ): void {
        $contents = Yaml::dump($data, $inline, 4, Yaml::DUMP_NULL_AS_TILDE);
        if ($this->fileManager->fileExists($targetPath)) {
            $contents = $this->mergeYamls(
                $this->fileManager->getFileContents($targetPath),
                $contents,
                $data,
                $inline,
                $forceArrayMerge
            );
        }

        $this->dumpYaml($targetPath, $contents);
    }

    private function mergeYamls(
        string $existingContent,
        string $newContent,
        array $newData,
        int $inline = 4,
        bool $forceArrayMerge = false
    ): string {
        $existingData = Yaml::parse($existingContent);
        $existingKeys = array_keys($newData);
        $newKeys = array_keys($existingData);
        if (!$forceArrayMerge && count($existingKeys) === 1 && $existingKeys === $newKeys) {
            $contents = $existingContent . substr($newContent, strpos($newContent, "\n") + 1);
        } else {
            $manipulator = new YamlSourceManipulator($existingContent);
            $newData = ArrayUtil::arrayMergeRecursiveDistinct($manipulator->getData(), $newData);
            try {
                $manipulator->setData($newData);
                $contents = $manipulator->getContents();
                // Force Yaml::DUMP_NULL_AS_TILDE
                $contents = preg_replace('/:\s+null$/m', ': ~', $contents);
            } catch (YamlManipulationFailedException $e) {
                $contents = Yaml::dump($newData, $inline, 4, Yaml::DUMP_NULL_AS_TILDE);
            }
        }

        return $contents;
    }

    private function dumpYaml(string $targetPath, string $contents): void
    {
        // Leave only 1 new line at the end
        $contents = trim($contents) . PHP_EOL;
        // Do not add new line after the first section
        $contents = preg_replace('/^(\w+:)\n+/m', "$1\n", $contents);

        parent::dumpFile($targetPath, $contents);
    }
}
