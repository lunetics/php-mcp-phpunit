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
    echo ""
    echo "Examples:"
    echo "  $0                  # Run tests (default)"
    echo "  $0 --test           # Run tests"
    echo "  $0 --shell          # Interactive shell"
    echo ""
    echo "Note: MCP server cannot run in Docker due to stdio transport requirements"
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
    *)
        echo "Unknown option: $1"
        echo "Note: MCP server requires stdio transport and cannot run in Docker"
        usage
        exit 1
        ;;
esac