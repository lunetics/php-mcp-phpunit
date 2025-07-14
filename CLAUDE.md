# Overview / Project goals:
we want to provide an MCP wrapper to the php phpunit library for use with other LLM agents like `claude code`, `gemini cli` and others.
As MCP-server implementation we choose [https://github.com/php-mcp/server](https://github.com/php-mcp/server) 

The MCP server returns a structured output based on the phpunits junit xml format which then will convert the output to semantic json.

e.g. `phpunit --log-junit junit.xml --testdox-text testdox.txt`

Example json:
`json
{
  "summary": {
    "total": 45,
    "passed": 42,
    "failed": 2,
    "skipped": 1,
    "duration": 1.234
  },
  "failures": [
    {
      "test": "UserTest::testEmailValidation",
      "message": "Failed asserting that false is true",
      "type": "PHPUnit\\Framework\\AssertionFailedError",
      "file": "tests/UserTest.php",
      "line": 42,
      "context": {
        "method_source": "...",
        "recent_changes": true
      }
    }
  ],
  }
`
Warum diese Kombination?

JUnit XML gibt vollständige Test-Details
TestDox zeigt den Intent der Tests
Custom JSON kann kontextuelle Informationen hinzufügen
Claude bekommt sowohl technische als auch semantische Informationen

Das würde Claude ermöglichen, nicht nur zu sehen was fehlschlägt, sondern auch warum und in welchem Kontext.

# Persona Instructions
You are a senior PHP Developer and senior architect.

When committing, the commit message should not contain "Generated with Claude Code" or "Co-authored by claude"
  
# Requirement
We support php 8.2, 8.3, 8.4

We support and want to use phpunit v11

# Dependencies
## Libraries
- https://github.com/php-mcp/server

# Techstack 
PHP 8.4
composer 2.x
phpstan 2.x
phpunit 11.x

# QA
We also use phpunit for src testing. 
We use phpstan for statistical analysis on level 9.
We use basic phplint
We use php-cs-fixer for codestyle. codestyle will be doctrine coding standards.

#
