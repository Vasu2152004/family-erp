# Docker Email Configuration Guide

## When to Use This Guide

Use these commands whenever you:
- Change email settings in `.env` file
- Update `MAIL_USERNAME` or `MAIL_PASSWORD`
- Change SMTP host, port, or encryption
- Experience email sending issues

## Quick Fix Commands (Run All)

When you change email settings, run these commands in order:

### 1. Clear All Caches
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
```

### 2. Flush Failed Jobs (Clear Old Failed Emails)
```bash
docker-compose exec app php artisan queue:flush
```

### 3. Restart Queue Worker (Required!)
```bash
docker-compose restart queue
```

### 4. Verify Queue Worker is Running
```bash
docker-compose ps queue
```

You should see status as "Up" or "running".

### 5. Test Email Configuration
```bash
docker-compose exec app php artisan mail:test your-email@gmail.com
```

Replace `your-email@gmail.com` with your actual email address.

---

## Complete Command Sequence (Copy & Paste)

Run all commands at once:

```bash
# Step 1: Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear

# Step 2: Flush failed jobs
docker-compose exec app php artisan queue:flush

# Step 3: Restart queue worker
docker-compose restart queue

# Step 4: Wait a few seconds for queue to start
sleep 5

# Step 5: Verify queue is running
docker-compose ps queue

# Step 6: Test email (replace with your email)
docker-compose exec app php artisan mail:test your-email@gmail.com
```

---

## Individual Commands Reference

### Cache Management

**Clear configuration cache:**
```bash
docker-compose exec app php artisan config:clear
```

**Clear application cache:**
```bash
docker-compose exec app php artisan cache:clear
```

**Clear route cache:**
```bash
docker-compose exec app php artisan route:clear
```

**Clear all caches at once:**
```bash
docker-compose exec app php artisan config:clear && docker-compose exec app php artisan cache:clear && docker-compose exec app php artisan route:clear
```

### Queue Management

**Flush all failed jobs:**
```bash
docker-compose exec app php artisan queue:flush
```

**View failed jobs:**
```bash
docker-compose exec app php artisan queue:failed
```

**Retry all failed jobs:**
```bash
docker-compose exec app php artisan queue:retry all
```

**Retry specific failed job:**
```bash
docker-compose exec app php artisan queue:retry {job-id}
```

**Delete specific failed job:**
```bash
docker-compose exec app php artisan queue:forget {job-id}
```

### Queue Worker Management

**Restart queue worker:**
```bash
docker-compose restart queue
```

**Stop queue worker:**
```bash
docker-compose stop queue
```

**Start queue worker:**
```bash
docker-compose start queue
```

**Check queue worker status:**
```bash
docker-compose ps queue
```

**View queue worker logs (real-time):**
```bash
docker-compose logs -f queue
```

**View last 50 lines of queue logs:**
```bash
docker-compose logs --tail=50 queue
```

### Email Testing

**Test email with authenticated user's email:**
```bash
docker-compose exec app php artisan mail:test
```

**Test email with specific address:**
```bash
docker-compose exec app php artisan mail:test your-email@gmail.com
```

**Verify email configuration:**
```bash
docker-compose exec app php artisan tinker --execute="echo 'Host: ' . config('mail.mailers.smtp.host') . PHP_EOL; echo 'Port: ' . config('mail.mailers.smtp.port') . PHP_EOL; echo 'Username: ' . config('mail.mailers.smtp.username') . PHP_EOL; echo 'Password: ' . (config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET') . PHP_EOL; echo 'Encryption: ' . config('mail.mailers.smtp.encryption') . PHP_EOL; echo 'From: ' . config('mail.from.address') . PHP_EOL;"
```

### Application Logs

**View Laravel logs (real-time):**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

**View last 100 lines of Laravel logs:**
```bash
docker-compose exec app tail -100 storage/logs/laravel.log
```

**Search logs for email errors:**
```bash
docker-compose exec app tail -200 storage/logs/laravel.log | grep -i "mail\|smtp\|error\|exception"
```

---

## Complete Restart (Nuclear Option)

If nothing works, restart everything:

```bash
# Stop all containers
docker-compose down

# Clear caches (if containers are still accessible)
docker-compose exec app php artisan config:clear 2>/dev/null || echo "Containers stopped"

# Start all containers
docker-compose up -d

# Wait for services to start
sleep 10

# Verify queue is running
docker-compose ps queue

# Test email
docker-compose exec app php artisan mail:test your-email@gmail.com
```

---

## Gmail App Password Setup

### Prerequisites
1. **Enable 2-Step Verification** (Required)
   - Go to: https://myaccount.google.com/security
   - Enable "2-Step Verification"

2. **Create App Password**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "Family ERP" as the name
   - Click "Generate"
   - Copy the 16-character password (remove spaces!)

### .env Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=abcdefghijklmnop  # 16 characters, NO SPACES!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Family ERP"
```

**Important Notes:**
- App password must be 16 characters
- Remove ALL spaces from the app password
- Use port 587 with TLS (not 465 with SSL)
- `MAIL_FROM_ADDRESS` should match `MAIL_USERNAME`

---

## Troubleshooting

### Emails Not Sending

1. **Check queue worker is running:**
   ```bash
   docker-compose ps queue
   ```

2. **Check queue logs for errors:**
   ```bash
   docker-compose logs -f queue
   ```

3. **Check failed jobs:**
   ```bash
   docker-compose exec app php artisan queue:failed
   ```

4. **Verify configuration is loaded:**
   ```bash
   docker-compose exec app php artisan tinker --execute="echo config('mail.mailers.smtp.host');"
   ```

### Common Errors

**"Authentication failed"**
- Check app password has no spaces
- Verify 2-Step Verification is enabled
- Generate a new app password

**"Connection timeout"**
- Check firewall settings
- Verify port 587 is not blocked
- Try port 465 with SSL (change `MAIL_ENCRYPTION=ssl`)

**"Message blocked"**
- Check Gmail security settings
- Verify sender reputation
- Check spam folder

**"Queue worker not processing"**
- Restart queue: `docker-compose restart queue`
- Check logs: `docker-compose logs queue`
- Verify database connection

---

## Monitoring Commands

**Watch queue processing in real-time:**
```bash
docker-compose logs -f queue
```

**Watch application logs:**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

**Check queue worker resource usage:**
```bash
docker stats family_erp_queue
```

---

## Quick Reference Card

```bash
# After changing .env email settings, run:
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan queue:flush
docker-compose restart queue
docker-compose exec app php artisan mail:test your-email@gmail.com
```

---

## Notes

- **Always restart queue worker** after changing email settings
- **Clear config cache** to load new .env values
- **Flush failed jobs** to clear old errors
- **Test email** to verify configuration
- **Monitor logs** to catch errors early

---

## Support

If emails still don't work after following this guide:
1. Check `EMAIL_TROUBLESHOOTING.md` for detailed troubleshooting
2. Verify Gmail app password is correct
3. Check Gmail account for security alerts
4. Review queue logs for specific error messages

