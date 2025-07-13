#!/bin/bash

# Build script for Laravel Docker deployment

echo "🚀 Building Laravel Docker Image..."

# Check if .env file exists, if not copy from .env.docker
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.docker template..."
    cp .env.docker .env
    echo "⚠️  Please update .env file with your actual configuration"
fi

# Build Docker image
echo "🔨 Building Docker image..."
docker build -t laravel-backend:latest .

# Check if build was successful
if [ $? -eq 0 ]; then
    echo "✅ Docker image built successfully!"
    echo ""
    echo "🚀 To run the application:"
    echo "   docker-compose up -d"
    echo ""
    echo "🌐 Application will be available at:"
    echo "   - Laravel API: http://localhost:8000"
    echo "   - phpMyAdmin: http://localhost:8080"
    echo ""
    echo "🔧 To run migrations:"
    echo "   docker-compose exec laravel-app php artisan migrate"
    echo ""
    echo "📊 To seed database:"
    echo "   docker-compose exec laravel-app php artisan db:seed"
else
    echo "❌ Docker build failed!"
    exit 1
fi
