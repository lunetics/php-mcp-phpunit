<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit;

class ExecutionResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly ?string $junitXml = null,
        public readonly ?string $testdoxText = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return 0 === $this->exitCode;
    }

    public function hasJunitXml(): bool
    {
        return null !== $this->junitXml && '' !== $this->junitXml;
    }

    public function hasTestdoxText(): bool
    {
        return null !== $this->testdoxText && '' !== $this->testdoxText;
    }

    public function getErrorMessage(): string
    {
        if ($this->isSuccess()) {
            return '';
        }

        $message = "PHPUnit execution failed (exit code: {$this->exitCode})";

        if ('' !== $this->stderr) {
            $message .= "\nSTDERR: " . $this->stderr;
        }

        if ('' !== $this->stdout) {
            $message .= "\nSTDOUT: " . $this->stdout;
        }

        return $message;
    }
}
