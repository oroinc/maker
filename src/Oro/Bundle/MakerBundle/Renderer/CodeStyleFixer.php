<?php

namespace Oro\Bundle\MakerBundle\Renderer;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Execute Code Style fixers with applied Oro configs.
 */
class CodeStyleFixer
{
    private PhpExecutableFinder $phpExecutableFinder;

    public function __construct(
        private string $projectRoot
    ) {
        $this->phpExecutableFinder = new PhpExecutableFinder();
    }

    public function fixCodeStyles(
        string $sourceCodePath,
        ConsoleStyle $io
    ): void {
        $binDir = $this->projectRoot . '/bin';

        $this->runPhpCsFixer($binDir, $sourceCodePath, $io);
        $this->runPhpCbf($binDir, $sourceCodePath, $io);
    }

    private function runPhpCsFixer(string $binDir, string $sourceCodePath, ConsoleStyle $io): void
    {
        $configPath = $this->projectRoot . '/vendor/oro/platform/build/.php-cs-fixer.php';
        $scriptPath = $binDir . '/php-cs-fixer';
        if (!is_readable($configPath) || !is_executable($scriptPath)) {
            $io->warning('php-cs-fixer was not found');

            return;
        }

        $command = [
            $this->phpExecutableFinder->find(),
            $scriptPath,
            'fix',
            $sourceCodePath,
            '--config=' . $configPath
        ];
        $fixerProcess = new Process($command);
        $io->writeln($fixerProcess->getCommandLine());
        $fixerProcess->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );

        if (!$fixerProcess->isSuccessful()) {
            $io->writeln('php-cs-fixer failed');
        } else {
            $io->writeln('php-cs-fixer done');
        }
    }

    private function runPhpCbf(string $binDir, string $sourceCodePath, ConsoleStyle $io): void
    {
        $configPath = $this->projectRoot . '/vendor/oro/platform/build/Oro/phpcs.xml';
        $scriptPath = $binDir . '/phpcbf';
        if (!is_readable($configPath) || !is_executable($binDir . '/php-cs-fixer')) {
            $io->warning('phpcbf was not found');

            return;
        }

        $command = [
            $this->phpExecutableFinder->find(),
            $scriptPath,
            $sourceCodePath,
            '--extensions=php',
            '--standard=' . $configPath
        ];
        $fixerProcess = new Process($command);
        $io->writeln($fixerProcess->getCommandLine());
        $fixerProcess->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );
    }
}
