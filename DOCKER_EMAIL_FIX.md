# Fix Email Issues in Docker - Step by Step

## Quick Fix Steps

### 1. **Clear Configuration Cache**
```bash
docker-compose exec app php artisan config:clear
```

### 2. **Restart Queue Worker** (Required - emails are queued)
```bash
docker-compose restart queue
```

### 3. **Verify Queue Worker is Running**
```bash
docker-compose ps queue
```

You should see the queue container status as "Up" or "running".

### 4. **Check Queue Logs for Errors**
```bash
docker-compose logs -f queue
```

Look for any error messages related to SMTP, authentication, or mail sending.

### 5. **Test Email Configuration**

Test with a specific email:
```bash
docker-compose exec app php artisan mail:test your-email@gmail.com
```

## Common Issues After Changing .env

### Issue 1: Queue Worker Not Picked Up New Settings
**Solution:** Restart the queue worker
```bash
docker-compose restart queue
```

### Issue 2: Config Cache Still Has Old Values
**Solution:** Clear config cache
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

### Issue 3: App Password Format
**Important:** Gmail App Password should be:
- 16 characters
- NO spaces (remove all spaces if copied with spaces)
- Example: `abcd efgh ijkl mnop` → `abcdefghijklmnop`

### Issue 4: .env File Not Loaded
**Solution:** Restart all containers to reload .env
```bash
docker-compose down
docker-compose up -d
```

## Verify Your .env Settings

Make sure your `.env` file has:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password-no-spaces
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Family ERP"
```

## Check Failed Jobs

If emails are failing, check the failed jobs table:
```bash
docker-compose exec app php artisan queue:failed
```

To retry failed jobs:
```bash
docker-compose exec app php artisan queue:retry all
```

## Complete Restart (If Nothing Works)

1. **Stop all containers:**
```bash
docker-compose down
```

2. **Clear config cache:**
```bash
docker-compose exec app php artisan config:clear
```

3. **Start containers:**
```bash
docker-compose up -d
```

4. **Verify queue is running:**
```bash
docker-compose ps queue
```

5. **Test email:**
```bash
docker-compose exec app php artisan mail:test your-email@gmail.com
```

## Monitor Queue in Real-Time

Watch queue processing:
```bash
docker-compose logs -f queue
```

## Check Application Logs

Check for email errors:
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

