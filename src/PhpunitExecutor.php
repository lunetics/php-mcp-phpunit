<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit;

class PhpunitExecutor
{
    private string $workingDirectory;
    private string $phpunitBinary;
    private int $timeout;

    public function __construct(
        string $workingDirectory = '',
        string $phpunitBinary = 'phpunit',
        int $timeout = 300,
    ) {
        $this->workingDirectory = $workingDirectory ?: (getcwd() ?: '.');
        $this->phpunitBinary    = $phpunitBinary;
        $this->timeout          = $timeout;
    }

    /**
     * @param array<string> $additionalArgs
     */
    public function runTests(
        string $path = '',
        array $additionalArgs = [],
    ): ExecutionResult {
        $outputDir = $this->createTempOutputDir();

        $command = [
            $this->phpunitBinary,
            '--log-junit', $outputDir . '/junit.xml',
            '--testdox-text', $outputDir . '/testdox.txt',
        ];

        if ('' !== $path) {
            $command[] = $path;
        }

        $command = array_merge($command, $additionalArgs);

        return $this->executeCommand($command, $outputDir);
    }

    /**
     * @param array<string> $additionalArgs
     */
    public function runSpecificTest(
        string $filter,
        string $path = '',
        array $additionalArgs = [],
    ): ExecutionResult {
        $outputDir = $this->createTempOutputDir();

        $command = [
            $this->phpunitBinary,
            '--log-junit', $outputDir . '/junit.xml',
            '--testdox-text', $outputDir . '/testdox.txt',
            '--filter', $filter,
        ];

        if ('' !== $path) {
            $command[] = $path;
        }

        $command = array_merge($command, $additionalArgs);

        return $this->executeCommand($command, $outputDir);
    }

    public function listTests(string $path = ''): ExecutionResult
    {
        $command = [$this->phpunitBinary, '--list-tests'];

        if ('' !== $path) {
            $command[] = $path;
        }

        return $this->executeCommand($command);
    }

    public function getConfiguration(): ExecutionResult
    {
        $command = [$this->phpunitBinary, '--version'];

        return $this->executeCommand($command);
    }

    /**
     * @param array<string> $command
     */
    private function executeCommand(array $command, ?string $outputDir = null): ExecutionResult
    {
        $commandString = implode(' ', array_map('escapeshellarg', $command));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $commandString,
            $descriptors,
            $pipes,
            $this->workingDirectory
        );

        if (!\is_resource($process)) {
            throw new \RuntimeException('Failed to start PHPUnit process');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $junitXml    = null;
        $testdoxText = null;

        if (null !== $outputDir) {
            $junitFile   = $outputDir . '/junit.xml';
            $testdoxFile = $outputDir . '/testdox.txt';

            if (file_exists($junitFile)) {
                $junitXml = file_get_contents($junitFile) ?: null;
            }

            if (file_exists($testdoxFile)) {
                $testdoxText = file_get_contents($testdoxFile) ?: null;
            }

            $this->cleanupTempDir($outputDir);
        }

        return new ExecutionResult(
            $exitCode,
            $stdout ?: '',
            $stderr ?: '',
            $junitXml,
            $testdoxText
        );
    }

    private function createTempOutputDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/mcp-phpunit-' . uniqid();

        if (!mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException('Failed to create temporary output directory');
        }

        return $tempDir;
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->cleanupTempDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
