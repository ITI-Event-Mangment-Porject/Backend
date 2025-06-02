# Laravel Backend Project

This is a ITI Event Laravel backend project setup with various configurations for authentication, caching, database, and more.

## Requirements

- PHP >= 8.0
- Composer
- MySQL/MariaDB
- Node.js & NPM (for Vite)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/ITI-Event-Mangment-Porject/Backend
cd Backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install NPM dependencies:
```bash
npm install
```

4. Copy the environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

## Configuration

### Key Features

- **Authentication**: Uses Laravel's built-in authentication system
- **Database**: MySQL configured by default
- **Caching**: Database-based caching enabled
- **Session**: Database-based session handling
- **Queue**: Database queue driver configured
- **CORS**: Configured for local development (http://localhost:5173)
- **Mail**: Log driver configured for development
- **Filesystem**: Local disk configured by default

### Environment Variables

Important environment variables to configure:

- `APP_NAME`: Your application name
- `APP_ENV`: Application environment (local/production)
- `APP_DEBUG`: Debug mode (true/false)
- `APP_URL`: Your application URL
- `DB_*`: Database configuration
- `MAIL_*`: Mail configuration
- `QUEUE_CONNECTION`: Queue driver settings
- `SESSION_*`: Session configuration

## Development

1. Start the development server:
```bash
php artisan serve
```

2. Start Vite for asset compilation:
```bash
npm run dev
```

## Database Setup

1. Run migrations:
```bash
php artisan migrate
```

2. (Optional) Seed the database:
```bash
php artisan db:seed
```

## Testing

Run PHPUnit tests:
```bash
php artisan test
```

## Maintenance

- Clear cache:
```bash
php artisan cache:clear
```

- Clear config cache:
```bash
php artisan config:clear
```

- Clear route cache:
```bash
php artisan route:clear
```

## Directory Structure

- `app/` - Contains the core code of your application
- `config/` - All configuration files
- `database/` - Database migrations and seeders
- `public/` - Publicly accessible files
- `resources/` - Views, raw assets, and translations
- `routes/` - All route definitions
- `storage/` - Application files, logs, and compiled files
- `tests/` - Application tests

## API Structure

### Base API Response Format

All API responses follow a standardized format using `BaseApiController`:

```json
// Success Response
{
    "success": true,
    "data": { /* response data */ },
    "message": "Success message"
}

// Error Response
{
    "success": false,
    "message": "Error message",
    "errors": { /* detailed error messages */ }
}
```

### Available Routes

#### Public Routes
- `POST /api/register` - Register new user
- `POST /api/login` - User login

#### Protected Routes (Requires Authentication)
- `GET /api/profile` - Get authenticated user's profile
- `POST /api/logout` - Logout user

### Authentication

The API uses Laravel Sanctum for authentication. Include the token in your requests:
```http
Authorization: Bearer your_token_here
```

Example login response:
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "access_token": "1|example_token",
        "token_type": "Bearer"
    },
    "message": "User logged in successfully."
}
```

## Security

Remember to:
- Keep your `.env` file secure and never commit it
- Set proper permissions on storage and cache directories
- Keep all packages updated
- Set `APP_DEBUG=false` in production

## Additional Information

- CORS is configured to allow requests from `http://localhost:5173`
- The application uses database-based caching and session handling
- Queue system is configured to use the database driver
- Mail is set to use the log driver in development

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).