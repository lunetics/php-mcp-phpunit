FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    dom \
    xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Make executable files executable
RUN chmod +x bin/mcp-phpunit-server docker-entrypoint.sh

# Create a non-root user
RUN useradd -m -u 1000 phpuser && chown -R phpuser:phpuser /app
USER phpuser

# Use entrypoint script
ENTRYPOINT ["./docker-entrypoint.sh"]

# Default command (run tests - MCP server cannot run in Docker)
CMD ["--test"]