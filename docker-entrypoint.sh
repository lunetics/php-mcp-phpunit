#!/bin/bash
set -e

# Function to display usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --help, -h          Show this help message"
    echo "  --test              Run PHPUnit tests"
    echo "  --shell             Start an interactive shell"
    echo "  --version           Show PHPUnit version"
    echo "  --http [options]    Start MCP server via HTTP transport"
    echo ""
    echo "Examples:"
    echo "  $0                  # Run tests (default)"
    echo "  $0 --test           # Run tests"
    echo "  $0 --shell          # Interactive shell"
    echo "  $0 --http           # Start MCP server on http://0.0.0.0:8080"
    echo "  $0 --http --port 9000  # Start MCP server on port 9000"
    echo ""
    echo "Note: MCP server supports HTTP transport for Docker usage"
    echo ""
}

# Parse command line arguments
case "${1:-test}" in
    --help|-h)
        usage
        exit 0
        ;;
    --test|test)
        echo "Running PHPUnit tests..."
        exec vendor/bin/phpunit
        ;;
    --shell|shell)
        echo "Starting interactive shell..."
        exec /bin/bash
        ;;
    --version|version)
        echo "PHPUnit version:"
        vendor/bin/phpunit --version
        echo ""
        echo "MCP PHPUnit Server version:"
        php -r "echo 'MCP PHPUnit Server 1.0.0';"
        echo ""
        ;;
    --http|http)
        echo "Starting MCP PHPUnit HTTP Server..."
        shift  # Remove --http from arguments
        exec bin/mcp-phpunit-http-server "$@"
        ;;
    *)
        echo "Unknown option: $1"
        echo "Use --help for available options"
        usage
        exit 1
        ;;
esac