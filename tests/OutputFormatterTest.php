<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit\Tests;

use PhpMcp\Phpunit\OutputFormatter;
use PhpMcp\Phpunit\Parsers\TestdoxParser;
use PHPUnit\Framework\TestCase;

class OutputFormatterTest extends TestCase
{
    private OutputFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new OutputFormatter(new TestdoxParser());
    }

    public function testFormatTestResultsWithEmptyData(): void
    {
        $junitData = [
            'summary' => [
                'total'        => 0,
                'passed'       => 0,
                'failed'       => 0,
                'errors'       => 0,
                'failures'     => 0,
                'skipped'      => 0,
                'duration'     => 0.0,
                'success_rate' => 0.0,
            ],
            'failures' => [],
            'errors'   => [],
            'skipped'  => [],
        ];

        $result = $this->formatter->formatTestResults($junitData, [], true);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('failures', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('performance', $result);

        $this->assertSame('text', $result['content'][0]['type']);
        $this->assertStringContainsString('0/0 tests passed', $result['content'][0]['text']);
    }

    public function testFormatTestResultsWithFailures(): void
    {
        $junitData = [
            'summary' => [
                'total'        => 2,
                'passed'       => 1,
                'failed'       => 1,
                'errors'       => 0,
                'failures'     => 1,
                'skipped'      => 0,
                'duration'     => 1.5,
                'success_rate' => 50.0,
            ],
            'failures' => [
                [
                    'test'    => 'UserTest::testEmailValidation',
                    'class'   => 'UserTest',
                    'method'  => 'testEmailValidation',
                    'file'    => '/path/to/UserTest.php',
                    'line'    => 42,
                    'message' => 'Failed asserting that false is true',
                    'type'    => 'PHPUnit\\Framework\\AssertionFailedError',
                    'time'    => 0.5,
                ],
            ],
            'errors'  => [],
            'skipped' => [],
        ];

        $testdoxData = [
            'UserTest' => [
                [
                    'method'      => 'testEmailValidation',
                    'description' => 'Email validation should reject invalid emails',
                    'status'      => 'failed',
                ],
            ],
        ];

        $result = $this->formatter->formatTestResults($junitData, $testdoxData, false);

        $this->assertStringContainsString('1/2 tests passed', $result['content'][0]['text']);
        $this->assertStringContainsString('(1 failed)', $result['content'][0]['text']);
        $this->assertStringContainsString('50.0% success rate', $result['content'][0]['text']);

        $this->assertCount(1, $result['failures']);
        $this->assertSame('Email validation should reject invalid emails', $result['failures'][0]['testdox']);
        $this->assertSame('error', $result['failures'][0]['severity']);
        $this->assertSame('/path/to/UserTest.php:42', $result['failures'][0]['location']);
    }

    public function testFormatListTests(): void
    {
        $listOutput = "UserTest::testEmailValidation\nUserTest::testPasswordValidation\nOrderTest::testCalculation";

        $result = $this->formatter->formatListTests($listOutput);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('tests', $result);
        $this->assertArrayHasKey('total_count', $result);

        $this->assertSame(3, $result['total_count']);
        $this->assertStringContainsString('Found 3 available tests', $result['content'][0]['text']);
        $this->assertContains('UserTest::testEmailValidation', $result['tests']);
    }

    public function testFormatError(): void
    {
        $result = $this->formatter->formatError('Test error message', 2);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('error', $result);

        $this->assertStringContainsString('PHPUnit execution failed: Test error message', $result['content'][0]['text']);
        $this->assertSame('Test error message', $result['error']['message']);
        $this->assertSame(2, $result['error']['exit_code']);
    }
}
