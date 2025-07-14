<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit\Parsers;

class JunitXmlParser
{
    public function parse(string $xmlContent): array
    {
        if (empty($xmlContent)) {
            throw new \InvalidArgumentException('XML content cannot be empty');
        }

        $dom                     = new \DOMDocument();
        $dom->preserveWhiteSpace = false;

        if (!$dom->loadXML($xmlContent)) {
            throw new \InvalidArgumentException('Invalid XML content');
        }

        $xpath      = new \DOMXPath($dom);
        $testsuites = $xpath->query('//testsuites');

        if (0 === $testsuites->length) {
            throw new \InvalidArgumentException('No testsuites element found in XML');
        }

        $rootTestsuite = $testsuites->item(0);

        return [
            'summary'    => $this->extractSummary($rootTestsuite, $xpath),
            'testsuites' => $this->extractTestsuites($xpath),
            'failures'   => $this->extractFailures($xpath),
            'errors'     => $this->extractErrors($xpath),
            'skipped'    => $this->extractSkipped($xpath),
        ];
    }

    private function extractSummary(\DOMElement $rootElement, \DOMXPath $xpath): array
    {
        $allTestcases = $xpath->query('//testcase');
        $allFailures  = $xpath->query('//failure');
        $allErrors    = $xpath->query('//error');
        $allSkipped   = $xpath->query('//skipped');

        $total    = $allTestcases->length;
        $failures = $allFailures->length;
        $errors   = $allErrors->length;
        $skipped  = $allSkipped->length;
        $passed   = $total - $failures - $errors - $skipped;

        $time = (float) ($rootElement->getAttribute('time') ?: '0');

        return [
            'total'        => $total,
            'passed'       => $passed,
            'failed'       => $failures + $errors,
            'errors'       => $errors,
            'failures'     => $failures,
            'skipped'      => $skipped,
            'duration'     => $time,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0.0,
        ];
    }

    private function extractTestsuites(\DOMXPath $xpath): array
    {
        $testsuites     = [];
        $testsuiteNodes = $xpath->query('//testsuite[@file]');

        foreach ($testsuiteNodes as $node) {
            $testsuites[] = [
                'name'       => $node->getAttribute('name'),
                'file'       => $node->getAttribute('file'),
                'tests'      => (int) $node->getAttribute('tests'),
                'assertions' => (int) $node->getAttribute('assertions'),
                'failures'   => (int) $node->getAttribute('failures'),
                'errors'     => (int) $node->getAttribute('errors'),
                'time'       => (float) $node->getAttribute('time'),
            ];
        }

        return $testsuites;
    }

    private function extractFailures(\DOMXPath $xpath): array
    {
        $failures     = [];
        $failureNodes = $xpath->query('//testcase[failure]');

        foreach ($failureNodes as $testcase) {
            $failureNode = $xpath->query('failure', $testcase)->item(0);

            if ($failureNode) {
                $failures[] = [
                    'test'    => $testcase->getAttribute('class') . '::' . $testcase->getAttribute('name'),
                    'class'   => $testcase->getAttribute('class'),
                    'method'  => $testcase->getAttribute('name'),
                    'file'    => $testcase->getAttribute('file'),
                    'line'    => (int) $testcase->getAttribute('line'),
                    'message' => mb_trim($failureNode->textContent),
                    'type'    => $failureNode->getAttribute('type') ?: 'PHPUnit\\Framework\\AssertionFailedError',
                    'time'    => (float) $testcase->getAttribute('time'),
                ];
            }
        }

        return $failures;
    }

    private function extractErrors(\DOMXPath $xpath): array
    {
        $errors     = [];
        $errorNodes = $xpath->query('//testcase[error]');

        foreach ($errorNodes as $testcase) {
            $errorNode = $xpath->query('error', $testcase)->item(0);

            if ($errorNode) {
                $errors[] = [
                    'test'    => $testcase->getAttribute('class') . '::' . $testcase->getAttribute('name'),
                    'class'   => $testcase->getAttribute('class'),
                    'method'  => $testcase->getAttribute('name'),
                    'file'    => $testcase->getAttribute('file'),
                    'line'    => (int) $testcase->getAttribute('line'),
                    'message' => mb_trim($errorNode->textContent),
                    'type'    => $errorNode->getAttribute('type') ?: 'Error',
                    'time'    => (float) $testcase->getAttribute('time'),
                ];
            }
        }

        return $errors;
    }

    private function extractSkipped(\DOMXPath $xpath): array
    {
        $skipped      = [];
        $skippedNodes = $xpath->query('//testcase[skipped]');

        foreach ($skippedNodes as $testcase) {
            $skippedNode = $xpath->query('skipped', $testcase)->item(0);

            $skipped[] = [
                'test'    => $testcase->getAttribute('class') . '::' . $testcase->getAttribute('name'),
                'class'   => $testcase->getAttribute('class'),
                'method'  => $testcase->getAttribute('name'),
                'file'    => $testcase->getAttribute('file'),
                'line'    => (int) $testcase->getAttribute('line'),
                'message' => $skippedNode ? mb_trim($skippedNode->textContent) : 'Test skipped',
                'time'    => (float) $testcase->getAttribute('time'),
            ];
        }

        return $skipped;
    }
}
