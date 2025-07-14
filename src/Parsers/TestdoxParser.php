<?php

declare(strict_types=1);

namespace PhpMcp\Phpunit\Parsers;

class TestdoxParser
{
    /**
     * @return array<string, array<array<string, string>>>
     */
    public function parse(string $testdoxContent): array
    {
        if (empty($testdoxContent)) {
            return [];
        }

        $lines        = explode("\n", $testdoxContent);
        $testdoxData  = [];
        $currentClass = null;

        foreach ($lines as $line) {
            $line = mb_trim($line);

            if (empty($line)) {
                continue;
            }

            if ($this->isTestClassLine($line)) {
                $currentClass = $this->extractClassName($line);
                if (!isset($testdoxData[$currentClass])) {
                    $testdoxData[$currentClass] = [];
                }
            } elseif ($this->isTestMethodLine($line) && null !== $currentClass) {
                $testMethod = $this->extractTestMethod($line);
                if (null !== $testMethod) {
                    $testdoxData[$currentClass][] = $testMethod;
                }
            }
        }

        return $testdoxData;
    }

    /**
     * @param array<string, array<array<string, string>>> $testdoxData
     */
    public function getTestDescription(string $className, string $methodName, array $testdoxData): ?string
    {
        if (!isset($testdoxData[$className])) {
            return null;
        }

        foreach ($testdoxData[$className] as $testMethod) {
            if ($testMethod['method'] === $methodName) {
                return $testMethod['description'];
            }
        }

        return null;
    }

    private function isTestClassLine(string $line): bool
    {
        return !str_starts_with($line, ' ')
               && !str_starts_with($line, '✓')
               && !str_starts_with($line, '✗')
               && !str_starts_with($line, '∅')
               && !empty($line);
    }

    private function isTestMethodLine(string $line): bool
    {
        return str_starts_with($line, ' ✓')
               || str_starts_with($line, ' ✗')
               || str_starts_with($line, ' ∅');
    }

    private function extractClassName(string $line): string
    {
        return mb_trim($line);
    }

    /**
     * @return array<string, string>|null
     */
    private function extractTestMethod(string $line): ?array
    {
        $line = mb_trim($line);

        if (str_starts_with($line, '✓')) {
            $status      = 'passed';
            $description = mb_trim(mb_substr($line, 1));
        } elseif (str_starts_with($line, '✗')) {
            $status      = 'failed';
            $description = mb_trim(mb_substr($line, 1));
        } elseif (str_starts_with($line, '∅')) {
            $status      = 'skipped';
            $description = mb_trim(mb_substr($line, 1));
        } else {
            return null;
        }

        $methodName = $this->convertDescriptionToMethodName($description);

        return [
            'method'      => $methodName,
            'description' => $description,
            'status'      => $status,
        ];
    }

    private function convertDescriptionToMethodName(string $description): string
    {
        $methodName = preg_replace('/[^a-zA-Z0-9\s]/', '', $description) ?? '';
        $methodName = preg_replace('/\s+/', ' ', $methodName) ?? '';
        $methodName = ucwords(mb_trim($methodName));
        $methodName = str_replace(' ', '', $methodName);

        return 'test' . $methodName;
    }
}
