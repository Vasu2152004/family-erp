# Production Deployment Checklist

This checklist ensures your Family ERP application is ready for production deployment.

## Pre-Deployment Checklist

### Security
- [ ] All hardcoded passwords/secrets removed from code
- [ ] `.env` file is not committed to version control
- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] Strong `APP_KEY` generated and set
- [ ] Database passwords are strong and unique
- [ ] Session encryption enabled (`SESSION_ENCRYPT=true`)
- [ ] Secure cookies enabled (`SESSION_SECURE_COOKIE=true`)
- [ ] Rate limiting configured on authentication routes
- [ ] Security headers middleware active

### Configuration
- [ ] All required environment variables are set
- [ ] Database credentials are correct
- [ ] `APP_URL` matches production domain
- [ ] Cache driver is not `array` (use `database` or `redis`)
- [ ] Queue driver is not `sync` (use `database` or `redis`)
- [ ] Log channel set to `daily` for production
- [ ] Log level set to `error` for production

### Mail Configuration
- [ ] Mail driver configured (not `log` - use `smtp`, `postmark`, `ses`, or `resend`)
- [ ] `MAIL_FROM_ADDRESS` is set and valid
- [ ] `MAIL_FROM_NAME` is set
- [ ] SMTP credentials configured (if using SMTP):
  - [ ] `MAIL_HOST` is set
  - [ ] `MAIL_PORT` is set
  - [ ] `MAIL_USERNAME` is set
  - [ ] `MAIL_PASSWORD` is set
  - [ ] `MAIL_ENCRYPTION` is set (tls/ssl)
- [ ] Mail test command executed successfully (`php artisan mail:test`)
- [ ] Queue workers running for email delivery
- [ ] Email notifications tested (document reminders, vehicle reminders, etc.)

### Database
- [ ] Database backups configured
- [ ] All migrations have been tested
- [ ] Database indexes added (run migration)
- [ ] Database connection tested
- [ ] Foreign key constraints verified

### Code Quality
- [ ] All tests passing
- [ ] Code formatted with Laravel Pint
- [ ] No debug code or `dd()` statements
- [ ] Error handling implemented
- [ ] Custom error pages created

### Assets & Build
- [ ] Assets compiled for production (`npm run build`)
- [ ] Asset versioning enabled
- [ ] CDN configured (if applicable)

### Docker (if using)
- [ ] Production Dockerfile optimized
- [ ] `docker-compose.production.yml` configured
- [ ] Environment variables loaded from `.env`
- [ ] Health checks configured
- [ ] Proper restart policies set
- [ ] Queue worker service configured and running
- [ ] Queue worker container has proper restart policy

## Deployment Steps

1. **Backup Current State**
   - [ ] Database backup created
   - [ ] File storage backup created
   - [ ] Current codebase tagged/backed up

2. **Environment Setup**
   - [ ] Production `.env` file created
   - [ ] Environment variables validated (`php artisan env:validate`)
   - [ ] SSL certificates installed (if using HTTPS)

3. **Code Deployment**
   - [ ] Latest code pulled from repository
   - [ ] Dependencies installed (`composer install --no-dev`)
   - [ ] Migrations run (`php artisan migrate --force`)
   - [ ] Configuration cached (`php artisan config:cache`)
   - [ ] Routes cached (`php artisan route:cache`)
   - [ ] Views cached (`php artisan view:cache`)

4. **Service Restart**
   - [ ] Application server restarted (php-fpm, nginx, etc.)
   - [ ] Queue workers restarted (if using queues)
   - [ ] Queue worker container running (if using Docker)
   - [ ] Scheduled tasks verified
   - [ ] Queue processing verified (check logs)

5. **Permissions**
   - [ ] Storage directories writable (`chmod -R 775 storage`)
   - [ ] Cache directories writable (`chmod -R 775 bootstrap/cache`)
   - [ ] Proper file ownership set

## Post-Deployment Verification

### Health Checks
- [ ] Health endpoint accessible (`/up`)
- [ ] Database connectivity verified
- [ ] Cache connectivity verified
- [ ] Queue connectivity verified (if using queues)

### Functionality Tests
- [ ] User registration works
- [ ] User login works
- [ ] Dashboard loads correctly
- [ ] Critical features tested (create/edit/delete operations)
- [ ] File uploads work
- [ ] Email notifications are sent and received
- [ ] Test email sent successfully (`php artisan mail:test`)
- [ ] Queue workers processing jobs correctly

### Performance
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] No N+1 query issues
- [ ] Caching working correctly

### Monitoring
- [ ] Error logging configured
- [ ] Log rotation working
- [ ] Monitoring tools configured (if applicable)
- [ ] Alerts set up for critical errors

### Security
- [ ] HTTPS enforced (if applicable)
- [ ] Security headers present
- [ ] Rate limiting active
- [ ] No sensitive data exposed in errors

## Rollback Procedure

If deployment fails:

1. **Immediate Rollback**
   - [ ] Restore previous code version
   - [ ] Restore database from backup
   - [ ] Restore file storage from backup
   - [ ] Clear all caches
   - [ ] Restart services

2. **Investigation**
   - [ ] Check application logs
   - [ ] Check server logs
   - [ ] Review error messages
   - [ ] Identify root cause

3. **Fix and Redeploy**
   - [ ] Fix identified issues
   - [ ] Test fixes in staging
   - [ ] Follow deployment steps again

## Emergency Contacts

- **System Administrator**: [Contact Info]
- **Database Administrator**: [Contact Info]
- **Development Team**: [Contact Info]
- **Hosting Provider Support**: [Contact Info]

## Maintenance Windows

- **Scheduled Maintenance**: [Day/Time]
- **Emergency Maintenance**: [Procedure]

## Notes

- Always test in staging environment first
- Keep deployment logs for audit purposes
- Document any custom configurations
- Update this checklist based on your specific requirements

