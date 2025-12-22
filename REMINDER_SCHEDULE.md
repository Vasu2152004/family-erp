# Email Reminder Schedule

## Overview
The Family ERP system automatically sends email reminders for important events and expirations. All reminders are processed daily at scheduled times.

## Reminder Types & Schedule

### 1. Document Expiry Reminders
**Command:** `documents:send-expiry-reminders`  
**Schedule:** Daily at 8:00 AM  
**Reminder Days:**
- **30 days before expiry**
- **7 days before expiry**
- **On the expiry date**

**Supported Document Types:**
- Passport
- Driving License
- Insurance
- Custom document types with expiry support enabled

### 2. Vehicle Expiry Reminders
**Command:** `vehicles:send-expiry-reminders`  
**Schedule:** Daily at 8:00 AM  
**Reminder Days:**
- **30 days before expiry**
- **7 days before expiry**
- **On the expiry date**

**Reminder Types:**
- RC (Registration Certificate) expiry
- Insurance expiry
- PUC (Pollution Under Control) expiry

### 3. Event Reminders
**Command:** `calendar:send-reminders`  
**Schedule:** Every 5 minutes  
**Reminder Time:** Based on `reminder_before_minutes` setting for each event

### 4. Low Stock Alerts
**Command:** `inventory:check-low-stock`  
**Schedule:** Daily at 9:00 AM  
**Trigger:** When item quantity falls below minimum required quantity

### 5. Medicine Expiry Reminders
**Command:** `medicines:send-expiry-reminders`  
**Schedule:** Daily at 8:00 AM  
**Reminder Days:**
- **30 days before expiry**
- **7 days before expiry**
- **On the expiry date**

**Recipients:** All family members (no role restrictions)

### 6. Medicine Intake Reminders
**Command:** `medicines:send-intake-reminders`  
**Schedule:** Every 5 minutes  
**Reminder Types:**
- **Daily:** Every day at specified time
- **Weekly:** Selected days of week at specified time
- **Custom:** Specific dates at specified time

**Recipients:** All family members (no role restrictions)

### 7. Health Module Medicine Reminders
**Command:** `health:send-medicine-reminders`  
**Schedule:** Every 5 minutes  
**Reminder Time:** Based on scheduled medicine times from health module prescriptions

## How It Works

1. **Reminder Creation:**
   - When a document/vehicle is created or updated with an expiry date, the system automatically creates reminder records
   - Reminders are scheduled for 30 days, 7 days, and on the expiry date

2. **Reminder Processing:**
   - Scheduled commands run at their designated times
   - Commands check for reminders where `remind_at <= today` and `sent_at IS NULL`
   - Emails are sent to all family members with appropriate roles
   - Reminder is marked as sent (`sent_at` is set)

3. **Email Delivery:**
   - Emails are queued for better performance
   - Queue workers must be running to process and send emails
   - Modern, responsive email templates are used

## Number Formatting

All numeric values in emails are automatically formatted:
- **Integers:** Displayed without decimal points (e.g., `2` instead of `2.00`)
- **Decimals:** Displayed with up to 2 decimal places (e.g., `2.50` or `2.75`)

## Queue Workers

**Important:** Email notifications are queued. Make sure queue workers are running:

```bash
# Development
php artisan queue:work

# Production (with Supervisor)
# See PRODUCTION_CHECKLIST.md for setup instructions
```

## Testing Reminders

Test all email templates:
```bash
php artisan mail:test-templates your-email@example.com
```

Test individual reminder commands:
```bash
php artisan documents:send-expiry-reminders
php artisan vehicles:send-expiry-reminders
php artisan medicines:send-expiry-reminders
php artisan medicines:send-intake-reminders
php artisan inventory:check-low-stock
```