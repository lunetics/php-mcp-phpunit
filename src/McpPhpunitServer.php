<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit;

use PhpMcp\Phpunit\Parsers\JunitXmlParser;
use PhpMcp\Phpunit\Parsers\TestdoxParser;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;

class McpPhpunitServer
{
    private PhpunitExecutor $executor;
    private JunitXmlParser $junitParser;
    private TestdoxParser $testdoxParser;
    private OutputFormatter $outputFormatter;

    public function __construct(
        ?PhpunitExecutor $executor = null,
        ?JunitXmlParser $junitParser = null,
        ?TestdoxParser $testdoxParser = null,
        ?OutputFormatter $outputFormatter = null,
    ) {
        $this->executor        = $executor ?? new PhpunitExecutor();
        $this->junitParser     = $junitParser ?? new JunitXmlParser();
        $this->testdoxParser   = $testdoxParser ?? new TestdoxParser();
        $this->outputFormatter = $outputFormatter ?? new OutputFormatter($this->testdoxParser);
    }

    public function start(): void
    {
        $server = Server::make()
            ->withServerInfo('php-mcp-phpunit', '1.0.0')
            ->withTool(fn (string $path = '', array $phpunit_args = []) => $this->runTests($path, $phpunit_args), 'run_tests', 'Execute PHPUnit tests and return structured results with semantic information')
            ->withTool(fn (string $filter, string $path = '', array $phpunit_args = []) => $this->runSpecificTest($filter, $path, $phpunit_args), 'run_specific_test', 'Execute a specific test method, class, or filter pattern with detailed output')
            ->withTool(fn (string $path = '') => $this->listTests($path), 'list_tests', 'List all available tests in the specified path without executing them')
            ->withTool(fn () => $this->getConfiguration(), 'get_configuration', 'Get PHPUnit version and configuration information')
            ->build();

        $transport = new StdioServerTransport();
        $server->listen($transport);
    }

    public function getServer(): Server
    {
        return Server::make()
            ->withServerInfo('php-mcp-phpunit', '1.0.0')
            ->withTool(fn (string $path = '', array $phpunit_args = []) => $this->runTests($path, $phpunit_args), 'run_tests', 'Execute PHPUnit tests and return structured results with semantic information')
            ->withTool(fn (string $filter, string $path = '', array $phpunit_args = []) => $this->runSpecificTest($filter, $path, $phpunit_args), 'run_specific_test', 'Execute a specific test method, class, or filter pattern with detailed output')
            ->withTool(fn (string $path = '') => $this->listTests($path), 'list_tests', 'List all available tests in the specified path without executing them')
            ->withTool(fn () => $this->getConfiguration(), 'get_configuration', 'Get PHPUnit version and configuration information')
            ->build();
    }

    #[McpTool(
        name: 'run_tests',
        description: 'Execute PHPUnit tests and return structured results with semantic information'
    )]
    /**
     * @param array<string> $phpunit_args
     *
     * @return array<string, mixed>
     */
    public function runTests(
        string $path = '',
        array $phpunit_args = [],
    ): array {
        try {
            $result = $this->executor->runTests($path, $phpunit_args);

            if (!$result->hasJunitXml()) {
                return $this->outputFormatter->formatError(
                    'No JUnit XML output generated. ' . $result->getErrorMessage(),
                    $result->exitCode
                );
            }

            $junitData   = $this->junitParser->parse($result->junitXml ?? '');
            $testdoxData = $result->hasTestdoxText()
                ? $this->testdoxParser->parse($result->testdoxText ?? '')
                : [];

            return $this->outputFormatter->formatTestResults(
                $junitData,
                $testdoxData,
                $result->isSuccess()
            );
        } catch (\Exception $e) {
            return $this->outputFormatter->formatError(
                'Failed to execute tests: ' . $e->getMessage()
            );
        }
    }

    #[McpTool(
        name: 'run_specific_test',
        description: 'Execute a specific test method, class, or filter pattern with detailed output'
    )]
    /**
     * @param array<string> $phpunit_args
     *
     * @return array<string, mixed>
     */
    public function runSpecificTest(
        string $filter,
        string $path = '',
        array $phpunit_args = [],
    ): array {
        try {
            $result = $this->executor->runSpecificTest($filter, $path, $phpunit_args);

            if (!$result->hasJunitXml()) {
                return $this->outputFormatter->formatError(
                    'No JUnit XML output generated. ' . $result->getErrorMessage(),
                    $result->exitCode
                );
            }

            $junitData   = $this->junitParser->parse($result->junitXml ?? '');
            $testdoxData = $result->hasTestdoxText()
                ? $this->testdoxParser->parse($result->testdoxText ?? '')
                : [];

            return $this->outputFormatter->formatTestResults(
                $junitData,
                $testdoxData,
                $result->isSuccess()
            );
        } catch (\Exception $e) {
            return $this->outputFormatter->formatError(
                'Failed to execute specific test: ' . $e->getMessage()
            );
        }
    }

    #[McpTool(
        name: 'list_tests',
        description: 'List all available tests in the specified path without executing them'
    )]
    /**
     * @return array<string, mixed>
     */
    public function listTests(string $path = ''): array
    {
        try {
            $result = $this->executor->listTests($path);

            if (!$result->isSuccess()) {
                return $this->outputFormatter->formatError(
                    'Failed to list tests: ' . $result->getErrorMessage(),
                    $result->exitCode
                );
            }

            return $this->outputFormatter->formatListTests($result->stdout);
        } catch (\Exception $e) {
            return $this->outputFormatter->formatError(
                'Failed to list tests: ' . $e->getMessage()
            );
        }
    }

    #[McpTool(
        name: 'get_configuration',
        description: 'Get PHPUnit version and configuration information'
    )]
    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        try {
            $result = $this->executor->getConfiguration();

            if (!$result->isSuccess()) {
                return $this->outputFormatter->formatError(
                    'Failed to get configuration: ' . $result->getErrorMessage(),
                    $result->exitCode
                );
            }

            return $this->outputFormatter->formatConfiguration($result->stdout);
        } catch (\Exception $e) {
            return $this->outputFormatter->formatError(
                'Failed to get configuration: ' . $e->getMessage()
            );
        }
    }
}
