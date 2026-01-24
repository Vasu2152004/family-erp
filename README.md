# Family ERP System

A comprehensive multi-tenant Family ERP (Enterprise Resource Planning) system built with Laravel 12. This application helps families manage their finances, assets, investments, health records, documents, tasks, inventory, and more in a centralized, secure platform.

## Features

- **Multi-Tenant Architecture**: Complete tenant isolation with secure data separation
- **Family Management**: Manage family members, roles, and permissions
- **Finance Management**: Track income, expenses, budgets, and financial analytics
- **Asset & Investment Tracking**: Manage assets and investments with hidden/locked functionality
- **Health Records**: Store medical records, doctor visits, and prescriptions
- **Document Management**: Secure document storage with password protection
- **Task Management**: Assign and track household tasks
- **Inventory Management**: Track inventory items and shopping lists
- **Vehicle Management**: Track vehicles, service logs, and fuel entries
- **Calendar & Events**: Manage family calendar with reminders
- **Notes & Diary**: Private and shared notes with PIN protection

## Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js and npm (for asset compilation)

## Installation

1. Clone the repository and install dependencies:
```bash
composer install
npm install
```

2. Copy `.env.example` to `.env` and configure it:
```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations:
```bash
php artisan migrate
```

4. Build assets:
```bash
npm run build
```

5. Start the development server:
```bash
php artisan serve
```

## Environment Configuration

### Required Environment Variables

- `APP_NAME`: Application name
- `APP_ENV`: Environment (local, staging, production)
- `APP_DEBUG`: Debug mode (false for production)
- `APP_KEY`: Application encryption key
- `APP_URL`: Application URL
- `DB_HOST`: Database host
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password

See `.env.production.example` for a complete list of production environment variables.

### Mail Configuration

The application sends email notifications for various events (document expiry reminders, vehicle reminders, calendar events, low stock alerts, etc.). Configure your mail settings in the `.env` file.

#### SMTP Configuration (Recommended)

For production, use SMTP with a reliable mail service provider:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Family ERP"
```

#### Common SMTP Providers

**Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
```

**Outlook/Hotmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

**Mailtrap (Testing):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

#### Other Mail Providers

**Postmark:**
```env
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-postmark-token
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Family ERP"
```

**AWS SES:**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Family ERP"
```

**Resend:**
```env
MAIL_MAILER=resend
RESEND_KEY=your-resend-key
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Family ERP"
```

#### Development/Testing

For local development, you can use the log driver to write emails to log files:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Family ERP"
```

**Note:** The log driver should NOT be used in production as emails will not actually be sent.

#### Testing Mail Configuration

Test your mail configuration using the built-in command:

```bash
# Test with authenticated user's email
php artisan mail:test

# Test with specific email address
php artisan mail:test user@example.com
```

#### Queue Workers for Email Delivery

Since notifications are queued for better performance, you must run queue workers to process and send emails:

```bash
# Start queue worker
php artisan queue:work

# Or use queue:listen for development (auto-restarts on code changes)
php artisan queue:listen
```

For production, use a process manager like Supervisor to keep queue workers running. See the [Queue Worker Setup](#queue-worker-setup) section below.

## Deployment

### Pre-Deployment Checklist

1. **Environment Validation**: Run `php artisan env:validate` to check production readiness
2. **Security**: Ensure all hardcoded secrets are removed
3. **Configuration**: Set `APP_DEBUG=false` and `APP_ENV=production`
4. **Database**: Ensure database backups are configured
5. **SSL/HTTPS**: Configure SSL certificates for production

### Deployment Steps

1. **Using Deployment Script** (Recommended):
```bash
chmod +x deploy.sh
./deploy.sh
```

2. **Manual Deployment**:
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### Post-Deployment

1. Verify health check: `curl http://your-domain.com/up`
2. Check application logs: `tail -f storage/logs/laravel.log`
3. Monitor error rates and performance
4. Verify all services are running correctly

## Production Configuration

### Security Settings

- **Session Encryption**: Enabled by default in production
- **Secure Cookies**: Enabled when `APP_ENV=production`
- **Rate Limiting**: Configured on authentication routes
- **Security Headers**: HSTS, CSP, X-Frame-Options, etc.

### Performance Optimization

- **Caching**: Config, routes, and views are cached in production
- **Database Indexes**: Automatically added via migrations
- **Asset Optimization**: Assets are minified and versioned

### Monitoring

- **Health Check**: Available at `/up` endpoint
- **Logging**: Daily log rotation configured
- **Error Tracking**: All exceptions are logged with context

## Development

### Running Tests

```bash
php artisan test
```

### Code Formatting

```bash
./vendor/bin/pint
```

### Database Migrations

```bash
# Create migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback
```

### Queue Worker Setup

The application uses queues for email notifications and other background jobs. Queue workers must be running for emails to be sent.

#### Development

```bash
# Start queue worker (recommended for development)
php artisan queue:listen

# Or use queue:work (requires manual restart on code changes)
php artisan queue:work
```

#### Production with Supervisor

Create a supervisor configuration file at `/etc/supervisor/conf.d/family-erp-worker.conf`:

```ini
[program:family-erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

Then start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start family-erp-worker:*
```

## Project Structure

```
family-erp/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/
│   │   ├── Controllers/      # Application controllers
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Requests/         # Form request validation
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   ├── Services/             # Business logic services
│   └── Notifications/        # Notification classes
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   ├── views/                # Blade templates
│   └── js/                   # JavaScript files
├── routes/
│   ├── web.php               # Web routes
│   ├── console.php           # Console routes
│   └── health.php            # Health check routes
├── tests/                    # Test files
└── public/                   # Public assets
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**: Check `.env` database credentials
2. **Permission Errors**: Ensure `storage/` and `bootstrap/cache/` are writable
3. **Cache Issues**: Run `php artisan cache:clear` and `php artisan config:clear`
4. **Migration Errors**: Check database connection and run `php artisan migrate:fresh` (development only)

### Getting Help

- Check application logs: `storage/logs/laravel.log`
- Run environment validation: `php artisan env:validate`
- Check health endpoint: `curl http://localhost/up`

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
