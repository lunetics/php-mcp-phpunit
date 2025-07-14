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
- 🚀 **Dual Transport Support** - Stdio (local) and HTTP (Docker/remote)
- ⚡ **Full Docker Compatibility** via StreamableHttpServerTransport
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

### Using Docker

```bash
git clone https://github.com/php-mcp/phpunit.git
cd phpunit

# Build the image
docker build -t php-mcp-phpunit .

# Run MCP server with HTTP transport
docker run -p 8080:8080 php-mcp-phpunit --http

# Or run development/testing commands
docker run --rm php-mcp-phpunit --test
docker run --rm php-mcp-phpunit --shell
```

**Docker Support:**
- ✅ **MCP Server**: Via HTTP transport (`--http`)
- ✅ **Development**: Interactive shell (`--shell`) 
- ✅ **Testing**: Run PHPUnit tests (`--test`)
- ✅ **CI/CD**: Automated testing and quality checks

## Usage

### Starting the MCP Server

#### Option 1: Stdio Transport (Local Installation)
```bash
# Local installation with stdio transport
./bin/mcp-phpunit-server
```

#### Option 2: HTTP Transport (Docker Compatible)
```bash
# Local HTTP server
./bin/mcp-phpunit-http-server

# Or with Docker
docker run -p 8080:8080 php-mcp-phpunit --http

# Custom host and port
./bin/mcp-phpunit-http-server --host 0.0.0.0 --port 9000 --json
```

**HTTP Transport Endpoints:**
- `GET /mcp/sse` - Server-Sent Events endpoint for streaming responses
- `POST /mcp/message` - HTTP endpoint for sending requests

### Transport Options

#### Stdio Transport
- **Best for**: Local development, IDE integration, Claude Desktop
- **Requirements**: Direct process access, no network needed
- **Limitations**: Cannot run in Docker containers

#### HTTP Transport  
- **Best for**: Docker deployments, remote servers, cloud environments
- **Requirements**: Network access to HTTP endpoints
- **Benefits**: Works in any environment, Docker compatible

### MCP Client Configuration

#### For Stdio Transport (Claude Desktop)
```json
{
  "mcpServers": {
    "phpunit": {
      "command": "/path/to/bin/mcp-phpunit-server",
      "args": []
    }
  }
}
```

#### For HTTP Transport (Any MCP Client)
```json
{
  "mcpServers": {
    "phpunit": {
      "url": "http://localhost:8080/mcp/sse"
    }
  }
}
```

### Alternatives for Non-Docker Environments

If you need to use the MCP server but prefer not to use Docker, here are alternative approaches:

#### Option 1: Host PHP Installation
```bash
# Install PHP and Composer on the host system
sudo apt-get install php8.4-cli php8.4-dom php8.4-xml composer

# Install the MCP server globally
composer global require php-mcp/phpunit

# Add to PATH
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

#### Option 2: Development Container with Host Mount
```bash
# Use Docker for development, run MCP server on host
docker-compose exec -T php-dev vendor/bin/phpunit  # Testing
./bin/mcp-phpunit-server  # MCP server runs on host
```

#### Option 3: Remote Development Environment
- Use GitHub Codespaces, GitPod, or similar cloud IDEs
- These provide direct access to the development environment
- No Docker isolation issues

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

### Practical Usage Examples

#### Running All Tests
```bash
# Via Claude Desktop or MCP client
run_tests()
```

#### Running Tests in Specific Directory
```bash
run_tests(path: "tests/Unit")
```

#### Running Tests with Custom PHPUnit Arguments
```bash
run_tests(phpunit_args: ["--coverage-text", "--stop-on-failure"])
```

#### Running a Specific Test Method
```bash
run_specific_test(filter: "UserTest::testEmailValidation")
```

#### Running Tests with Filter Pattern
```bash
run_specific_test(filter: "Email", path: "tests/Unit")
```

#### Listing Available Tests
```bash
list_tests(path: "tests")
```

#### Getting PHPUnit Configuration
```bash
get_configuration()
```

### Real-World Workflow Examples

#### 1. Initial Test Assessment
```bash
# Get PHPUnit version and configuration
get_configuration()

# List all available tests
list_tests()

# Run all tests to get overview
run_tests()
```

#### 2. Debugging Failed Tests
```bash
# Run specific failing test with verbose output
run_specific_test(
    filter: "UserServiceTest::testCreateUser",
    phpunit_args: ["--verbose"]
)

# Run only failed tests group
run_tests(phpunit_args: ["--group", "failing"])
```

#### 3. Performance Testing
```bash
# Run tests with timing information
run_tests(phpunit_args: ["--log-junit", "results.xml"])

# Check slowest tests in performance data
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

# Docker (development)
docker-compose exec -T php-dev vendor/bin/phpunit

# Docker (production container)
docker run --rm php-mcp-phpunit --test
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

## Configuration

### Environment Variables

```bash
# Custom PHPUnit binary path
export PHPUNIT_BINARY="/usr/local/bin/phpunit"

# Default working directory
export PHPUNIT_WORKING_DIR="/path/to/project"

# Timeout for PHPUnit execution (seconds)
export PHPUNIT_TIMEOUT=300
```

### Advanced Configuration

#### Custom PHPUnit Configuration File
```bash
run_tests(phpunit_args: ["--configuration", "custom-phpunit.xml"])
```

#### Memory Limit and Timeout
```bash
run_tests(phpunit_args: ["--memory-limit", "512M"])
```

#### Test Coverage Options
```bash
run_tests(phpunit_args: ["--coverage-html", "coverage/"])
```

### Docker Environment Variables

```yaml
# docker-compose.yml
version: '3.8'
services:
  php-mcp-phpunit:
    environment:
      - PHPUNIT_BINARY=vendor/bin/phpunit
      - PHPUNIT_WORKING_DIR=/app
      - PHPUNIT_TIMEOUT=600
```

### Docker Usage Examples (Development Only)

⚠️ **Remember**: Docker cannot run the MCP server for client integration.

```bash
# Run tests in development environment
docker-compose exec -T php-dev vendor/bin/phpunit

# Run code quality checks
docker-compose exec -T php-dev vendor/bin/phpstan analyze
docker-compose exec -T php-dev vendor/bin/php-cs-fixer fix --dry-run

# Interactive shell for debugging
docker-compose exec php-dev bash

# Build and test the container (runs tests by default)
docker build -t php-mcp-phpunit .
docker run --rm php-mcp-phpunit
```

## Troubleshooting

### Common Issues

#### 1. "PHPUnit not found" Error
```bash
# Check if PHPUnit is installed
composer show phpunit/phpunit

# Install PHPUnit if missing
composer require --dev phpunit/phpunit:^11.0

# Verify installation
vendor/bin/phpunit --version
```

#### 2. "No tests executed" Warning
```bash
# Check test directory structure
list_tests(path: "tests")

# Verify PHPUnit configuration
get_configuration()

# Check for proper test naming (Test.php suffix)
find tests -name "*Test.php"
```

#### 3. MCP Server Connection Issues
```bash
# Verify MCP server binary (local installation required)
php -l bin/mcp-phpunit-server

# Test PHP installation
php --version

# Check if server can start
./bin/mcp-phpunit-server &
ps aux | grep mcp-phpunit-server
kill %1  # Stop the test server
```

#### 7. Docker Environment Issues
```bash
# Docker is for development only - cannot run MCP server
# If you see "MCP server not responding" with Docker:

# WRONG: Don't do this
# docker run php-mcp-phpunit  # Will not work for MCP clients

# CORRECT: Install locally
composer global require php-mcp/phpunit
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

#### 4. Permission Errors
```bash
# Fix file permissions
chmod +x bin/mcp-phpunit-server

# Fix directory permissions
chmod -R 755 tests/
```

#### 5. Memory Limit Issues
```bash
# Increase PHP memory limit
run_tests(phpunit_args: ["--memory-limit", "1G"])

# Check current memory usage
run_tests(phpunit_args: ["--verbose"])
```

#### 6. Timeout Issues
```bash
# Increase timeout for slow tests
run_tests(phpunit_args: ["--timeout", "300"])

# Run specific slow test
run_specific_test(filter: "SlowTest", phpunit_args: ["--timeout", "600"])
```

### Error Messages and Solutions

| Error Message | Solution |
|---------------|----------|
| `Class not found` | Run `composer dump-autoload` |
| `Permission denied` | Check file permissions with `chmod +x` |
| `Test directory not found` | Verify path parameter in `run_tests()` |
| `PHPUnit configuration invalid` | Check `phpunit.xml` syntax |
| `Memory limit exceeded` | Increase memory limit or optimize tests |
| `Connection timeout` | Increase timeout or check network |

### Debug Mode

Enable verbose output for debugging:
```bash
run_tests(phpunit_args: ["--verbose", "--debug"])
```

## Performance Guidelines

### Optimization Tips

#### 1. Test Execution Performance
- Use `--stop-on-failure` for quick feedback during development
- Implement test grouping with `@group` annotations
- Use data providers instead of multiple similar tests
- Avoid database operations in unit tests

#### 2. Memory Usage
- Default memory limit: 512MB
- Recommended for large test suites: 1GB
- Monitor memory usage with `--verbose` flag

#### 3. Parallel Testing
```bash
# Run tests in parallel (if supported)
run_tests(phpunit_args: ["--parallel", "4"])
```

#### 4. Test Selection
```bash
# Run only fast tests during development
run_tests(phpunit_args: ["--group", "fast"])

# Run comprehensive tests in CI
run_tests(phpunit_args: ["--group", "slow,integration"])
```

### Performance Limitations

- **Maximum execution time**: 300 seconds (configurable)
- **Memory limit**: 512MB (configurable)
- **Concurrent requests**: 1 (MCP stdio transport)
- **File size limit**: No limit on test files
- **Test count**: No theoretical limit

### Monitoring Performance

The server provides performance metrics in response:
```json
{
  "performance": {
    "total_duration": 1.234,
    "slowest_tests": [
      {
        "test": "SlowTest::testLargeDataset",
        "time": 0.89,
        "file": "tests/SlowTest.php"
      }
    ]
  }
}
```

## API Reference

### Complete Response Schemas

#### Success Response Format
```json
{
  "content": [
    {
      "type": "text",
      "text": "Human-readable summary"
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
      "test": "ClassName::methodName",
      "class": "ClassName",
      "method": "methodName", 
      "message": "Assertion failure message",
      "type": "Exception class name",
      "file": "path/to/test/file.php",
      "line": 42,
      "time": 0.123,
      "testdox": "Human readable test description",
      "severity": "error",
      "location": "file.php:42"
    }
  ],
  "errors": [
    {
      "test": "ClassName::methodName",
      "class": "ClassName",
      "method": "methodName",
      "message": "Error message",
      "type": "Error class name",
      "file": "path/to/test/file.php",
      "line": 42,
      "time": 0.001,
      "testdox": "Human readable test description",
      "severity": "error",
      "location": "file.php:42"
    }
  ],
  "skipped": [
    {
      "test": "ClassName::methodName",
      "class": "ClassName",
      "method": "methodName",
      "message": "Skip reason",
      "file": "path/to/test/file.php",
      "line": 15,
      "time": 0.001,
      "testdox": "Human readable test description",
      "severity": "info",
      "location": "file.php:15"
    }
  ],
  "performance": {
    "total_duration": 1.234,
    "slowest_tests": [
      {
        "test": "ClassName::methodName",
        "time": 0.89,
        "file": "path/to/test/file.php"
      }
    ]
  }
}
```

#### Error Response Format
```json
{
  "content": [
    {
      "type": "text",
      "text": "PHPUnit execution failed: Error message"
    }
  ],
  "error": {
    "message": "Detailed error message",
    "exit_code": 1
  }
}
```

#### List Tests Response Format
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

#### Configuration Response Format
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

### HTTP Status Codes Equivalent

| MCP Result | HTTP Equivalent | Description |
|------------|-----------------|-------------|
| Success with data | 200 OK | Tests executed successfully |
| Success with failures | 200 OK | Tests ran but some failed |
| Error response | 500 Internal Server Error | PHPUnit execution failed |
| Invalid parameters | 400 Bad Request | Invalid tool parameters |

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