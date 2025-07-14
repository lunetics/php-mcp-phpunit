<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit;

use PhpMcp\Phpunit\Parsers\TestdoxParser;

class OutputFormatter
{
    private TestdoxParser $testdoxParser;

    public function __construct(?TestdoxParser $testdoxParser = null)
    {
        $this->testdoxParser = $testdoxParser ?? new TestdoxParser();
    }

    public function formatTestResults(
        array $junitData,
        array $testdoxData,
        bool $isSuccess = true,
    ): array {
        $summary              = $junitData['summary'];
        $humanReadableSummary = $this->createHumanReadableSummary($summary, $isSuccess);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $humanReadableSummary,
                ],
            ],
            'summary'     => $summary,
            'failures'    => $this->enrichFailuresWithTestdox($junitData['failures'], $testdoxData),
            'errors'      => $this->enrichErrorsWithTestdox($junitData['errors'], $testdoxData),
            'skipped'     => $this->enrichSkippedWithTestdox($junitData['skipped'], $testdoxData),
            'performance' => $this->extractPerformanceMetrics($junitData),
        ];
    }

    public function formatListTests(string $listOutput): array
    {
        $lines = array_filter(explode("\n", $listOutput), fn ($line) => !empty(mb_trim($line)));
        $tests = [];

        foreach ($lines as $line) {
            $line = mb_trim($line);
            if (str_contains($line, '::')) {
                $tests[] = $line;
            }
        }

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => \sprintf('Found %d available tests', \count($tests)),
                ],
            ],
            'tests'       => $tests,
            'total_count' => \count($tests),
        ];
    }

    public function formatConfiguration(string $configOutput): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'PHPUnit configuration information',
                ],
            ],
            'configuration' => [
                'version_info' => mb_trim($configOutput),
            ],
        ];
    }

    public function formatError(string $errorMessage, int $exitCode = 1): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "PHPUnit execution failed: {$errorMessage}",
                ],
            ],
            'error' => [
                'message'   => $errorMessage,
                'exit_code' => $exitCode,
            ],
        ];
    }

    private function createHumanReadableSummary(array $summary, bool $isSuccess): string
    {
        $total       = $summary['total'];
        $passed      = $summary['passed'];
        $failed      = $summary['failed'];
        $skipped     = $summary['skipped'];
        $duration    = $summary['duration'];
        $successRate = $summary['success_rate'];

        $message = "PHPUnit Results: {$passed}/{$total} tests passed";

        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        if ($skipped > 0) {
            $message .= " ({$skipped} skipped)";
        }

        $message .= \sprintf(' - %.1f%% success rate in %.3fs', $successRate, $duration);

        if (!$isSuccess && $failed > 0) {
            $message .= ' ⚠️ Tests failed';
        } elseif ($isSuccess && 0 === $failed) {
            $message .= ' ✅ All tests passed';
        }

        return $message;
    }

    private function enrichFailuresWithTestdox(array $failures, array $testdoxData): array
    {
        return array_map(function ($failure) use ($testdoxData) {
            $testdox = $this->testdoxParser->getTestDescription(
                $failure['class'],
                $failure['method'],
                $testdoxData
            );

            return array_merge($failure, [
                'testdox'  => $testdox,
                'severity' => 'error',
                'location' => $failure['file'] . ':' . $failure['line'],
            ]);
        }, $failures);
    }

    private function enrichErrorsWithTestdox(array $errors, array $testdoxData): array
    {
        return array_map(function ($error) use ($testdoxData) {
            $testdox = $this->testdoxParser->getTestDescription(
                $error['class'],
                $error['method'],
                $testdoxData
            );

            return array_merge($error, [
                'testdox'  => $testdox,
                'severity' => 'error',
                'location' => $error['file'] . ':' . $error['line'],
            ]);
        }, $errors);
    }

    private function enrichSkippedWithTestdox(array $skipped, array $testdoxData): array
    {
        return array_map(function ($skip) use ($testdoxData) {
            $testdox = $this->testdoxParser->getTestDescription(
                $skip['class'],
                $skip['method'],
                $testdoxData
            );

            return array_merge($skip, [
                'testdox'  => $testdox,
                'severity' => 'info',
                'location' => $skip['file'] . ':' . $skip['line'],
            ]);
        }, $skipped);
    }

    private function extractPerformanceMetrics(array $junitData): array
    {
        $allTests = [];

        foreach (['failures', 'errors', 'skipped'] as $category) {
            $allTests = array_merge($allTests, $junitData[$category]);
        }

        usort($allTests, fn ($a, $b) => $b['time'] <=> $a['time']);

        $slowestTests = \array_slice($allTests, 0, 3);

        return [
            'total_duration' => $junitData['summary']['duration'],
            'slowest_tests'  => array_map(function ($test) {
                return [
                    'test' => $test['test'],
                    'time' => $test['time'],
                    'file' => $test['file'],
                ];
            }, $slowestTests),
        ];
    }
}
