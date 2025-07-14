# MCP PHPUnit Server

[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![PHPUnit](https://img.shields.io/badge/phpunit-11.x-green.svg)](https://phpunit.de)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A Model Context Protocol (MCP) server that provides structured PHPUnit test execution for LLM agents like Claude, Gemini CLI, and others.

## Overview

This MCP server wraps PHPUnit functionality and converts raw test output into structured, semantic JSON that's optimized for LLM consumption. It combines PHPUnit's JUnit XML output with TestDox descriptions to provide both technical details and human-readable context.

## Features

- 🧪 **4 Essential MCP Tools** for comprehensive test management
- 📊 **Structured Output** with pass/fail statistics and performance metrics
- 🔍 **Semantic Context** using PHPUnit TestDox for test descriptions
- ⚡ **Docker Support** for easy deployment and testing
- 🎯 **LLM-Optimized** JSON responses with human-readable summaries
- 🛡️ **Error Handling** with graceful degradation and informative messages

## Requirements

- PHP 8.2, 8.3, or 8.4
- PHPUnit 11.x
- Composer 2.x
- Extensions: `ext-dom`, `ext-json`

## Installation

### Using Composer

```bash
composer require php-mcp/phpunit
```

### Using Docker (Recommended for Development)

```bash
git clone https://github.com/php-mcp/phpunit.git
cd phpunit
docker-compose up -d
docker-compose exec -T php-dev composer install
```

## Usage

### Starting the MCP Server

```bash
# Local installation
./bin/mcp-phpunit-server

# Docker
docker-compose exec php-dev php bin/mcp-phpunit-server
```

### Configuring with Custom PHPUnit Path

```php
<?php
use PhpMcp\Phpunit\McpPhpunitServer;
use PhpMcp\Phpunit\PhpunitExecutor;

// Use custom PHPUnit binary path
$executor = new PhpunitExecutor(getcwd(), 'vendor/bin/phpunit');
$server = new McpPhpunitServer($executor);
$server->start();
```

## MCP Tools

### 1. `run_tests`
Execute PHPUnit tests and return structured results with semantic information.

**Parameters:**
- `path` (string, optional): Test directory or file path
- `phpunit_args` (array, optional): Additional PHPUnit arguments

**Example Output:**
```json
{
  "content": [
    {
      "type": "text",
      "text": "PHPUnit Results: 42/45 tests passed (2 failed, 1 skipped) - 93.3% success rate in 1.234s ⚠️ Tests failed"
    }
  ],
  "summary": {
    "total": 45,
    "passed": 42,
    "failed": 2,
    "skipped": 1,
    "duration": 1.234,
    "success_rate": 93.33
  },
  "failures": [
    {
      "test": "UserTest::testEmailValidation",
      "message": "Failed asserting that false is true",
      "type": "PHPUnit\\Framework\\AssertionFailedError",
      "file": "tests/UserTest.php",
      "line": 42,
      "testdox": "Email validation should reject invalid emails",
      "severity": "error",
      "location": "tests/UserTest.php:42"
    }
  ],
  "performance": {
    "total_duration": 1.234,
    "slowest_tests": [
      {
        "test": "DatabaseTest::testLargeDataset",
        "time": 0.89,
        "file": "tests/DatabaseTest.php"
      }
    ]
  }
}
```

### 2. `run_specific_test`
Execute a specific test method, class, or filter pattern with detailed output.

**Parameters:**
- `filter` (string): PHPUnit filter pattern (e.g., "UserTest::testMethod")
- `path` (string, optional): Test directory or file path
- `phpunit_args` (array, optional): Additional PHPUnit arguments

### 3. `list_tests`
List all available tests in the specified path without executing them.

**Parameters:**
- `path` (string, optional): Test directory or file path

**Example Output:**
```json
{
  "content": [
    {
      "type": "text",
      "text": "Found 15 available tests"
    }
  ],
  "tests": [
    "UserTest::testEmailValidation",
    "UserTest::testPasswordValidation",
    "OrderTest::testCalculation"
  ],
  "total_count": 15
}
```

### 4. `get_configuration`
Get PHPUnit version and configuration information.

**Example Output:**
```json
{
  "content": [
    {
      "type": "text",
      "text": "PHPUnit configuration information"
    }
  ],
  "configuration": {
    "version_info": "PHPUnit 11.5.27 by Sebastian Bergmann and contributors."
  }
}
```

## Integration with LLM Clients

### Claude Desktop

Add to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "phpunit": {
      "command": "/path/to/php-mcp-phpunit/bin/mcp-phpunit-server",
      "args": []
    }
  }
}
```

### Other MCP Clients

The server uses the standard MCP stdio transport and is compatible with any MCP client that supports the `2024-11-05` protocol version.

## Development

### Running Tests

```bash
# Local
vendor/bin/phpunit

# Docker
docker-compose exec -T php-dev vendor/bin/phpunit
```

### Code Quality

```bash
# PHP-CS-Fixer (Doctrine standards)
vendor/bin/php-cs-fixer fix

# PHPStan (Level 3)
vendor/bin/phpstan analyze
```

### Testing the MCP Server

```bash
# Docker
docker-compose exec -T php-dev php test-mcp-server.php
```

## Architecture

### Core Components

- **`McpPhpunitServer`**: Main MCP server with stdio transport
- **`PhpunitExecutor`**: Shell command wrapper for PHPUnit execution
- **`JunitXmlParser`**: Converts PHPUnit's JUnit XML output to structured arrays
- **`TestdoxParser`**: Extracts semantic test descriptions from TestDox output
- **`OutputFormatter`**: Formats data into MCP-compliant JSON responses

### Output Processing Flow

1. **Execute PHPUnit** with `--log-junit` and `--testdox-text` flags
2. **Parse XML** output for technical test details
3. **Parse TestDox** output for semantic test descriptions
4. **Merge & Format** into LLM-optimized JSON structure
5. **Return** structured response via MCP protocol

## Why This Combination?

- **JUnit XML** provides complete technical test details
- **TestDox** shows the intent and context of tests
- **Custom JSON** adds performance metrics and contextual information
- **MCP Format** ensures optimal consumption by LLM agents

This approach gives LLMs both technical accuracy and semantic understanding of test results.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure code quality checks pass
6. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Related Projects

- [php-mcp/server](https://github.com/php-mcp/server) - Core PHP MCP server library
- [Model Context Protocol](https://modelcontextprotocol.io/) - Official MCP specification
- [PHPUnit](https://phpunit.de/) - The PHP testing framework

## Support

- 📖 [Documentation](https://github.com/php-mcp/phpunit/wiki)
- 🐛 [Issue Tracker](https://github.com/php-mcp/phpunit/issues)
- 💬 [Discussions](https://github.com/php-mcp/phpunit/discussions)