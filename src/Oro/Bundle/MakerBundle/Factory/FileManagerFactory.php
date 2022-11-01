<?php

namespace Oro\Bundle\MakerBundle\Factory;

use Oro\Bundle\MakerBundle\Util\FileManager;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates fileManager instance with support of custom root directory
 */
class FileManagerFactory
{
    private Filesystem $fs;
    private AutoloaderUtil $autoloaderUtil;
    private MakerFileLinkFormatter $makerFileLinkFormatter;

    public function __construct(
        Filesystem $fs,
        AutoloaderUtil $autoloaderUtil,
        MakerFileLinkFormatter $makerFileLinkFormatter
    ) {
        $this->fs = $fs;
        $this->autoloaderUtil = $autoloaderUtil;
        $this->makerFileLinkFormatter = $makerFileLinkFormatter;
    }

    public function createFileManager(
        string $rootDirectory,
        string $twigDefaultPath = null
    ): FileManager {
        return new FileManager(
            $this->fs,
            $this->autoloaderUtil,
            $this->makerFileLinkFormatter,
            $rootDirectory,
            $twigDefaultPath
        );
    }
}
