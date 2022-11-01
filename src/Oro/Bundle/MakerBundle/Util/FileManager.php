<?php

namespace Oro\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\FileManager as BaseFileManager;

/**
 * Change behavior of maker's file manager to support custom namespaces and related directory structure.
 */
class FileManager extends BaseFileManager
{
    public function getRelativePathForFutureClass(string $className): ?string
    {
        return rtrim($this->getRootDirectory(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . str_replace('\\', '/', $className).'.php';
    }
}
