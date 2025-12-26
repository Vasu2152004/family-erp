# Simple Railway Docker Deployment

## Quick Start - Deploy Your Docker App to Railway

### Step 1: Push to GitHub
Make sure your code is on GitHub (Railway deploys from GitHub).

### Step 2: Create Railway Project

1. Go to [railway.app](https://railway.app)
2. Sign up/login with GitHub
3. Click **"New Project"**
4. Select **"Deploy from GitHub repo"**
5. Choose your repository

### Step 3: Railway Auto-Detects Docker

Railway will automatically:
- Detect your `Dockerfile` or `docker/Dockerfile`
- Start building your Docker image
- Deploy it

**If Railway doesn't detect Docker:**
1. Click on your service
2. Go to **"Settings"** tab
3. Under **"Build & Deploy"**:
   - **Dockerfile Path**: `docker/Dockerfile`
   - **Docker Build Context**: `.`
   - **Docker Build Target**: `production`

### Step 4: Add MySQL Database

1. In your Railway project, click **"+ New"**
2. Select **"Database"** → **"MySQL"**
3. Railway creates MySQL automatically
4. Note the connection details (you'll need them)

### Step 5: Set Environment Variables

**Where to find Environment Variables:**

1. Click on your **Project** (top level, not a service)
2. Click **"Variables"** tab (top menu)
3. This is where you add ALL environment variables

**Add these variables:**

```env
APP_NAME="Home Flow"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key-here
APP_URL=https://your-app-name.up.railway.app

# Database - Use Railway's MySQL service reference
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Home Flow"

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=database
SESSION_DRIVER=database
```

**Important:** 
- `${{MySQL.MYSQLHOST}}` references Railway's MySQL service
- Replace `MySQL` with your actual MySQL service name if different
- Railway automatically injects these variables

### Step 6: Add Queue Worker Service

1. Click **"+ New"** → **"Empty Service"**
2. Connect to the **same GitHub repo**
3. Go to **"Settings"**:
   - **Dockerfile Path**: `docker/Dockerfile`
   - **Docker Build Target**: `production`
   - **Start Command**: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`

### Step 7: Add Scheduler Service

1. Click **"+ New"** → **"Empty Service"**
2. Connect to the **same GitHub repo**
3. Go to **"Settings"**:
   - **Dockerfile Path**: `docker/Dockerfile`
   - **Docker Build Target**: `production`
   - **Start Command**: 
     ```bash
     sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
     ```

### Step 8: Generate APP_KEY

After first deployment:

1. Click on your **app service**
2. Go to **"Deployments"** tab
3. Click on latest deployment → **"View Logs"**
4. Or use Railway CLI:
   ```bash
   railway run php artisan key:generate
   ```
5. Copy the generated key
6. Add it to **Project Variables** as `APP_KEY`

### Step 9: Run Migrations

1. Click on your **app service**
2. Go to **"Deployments"** → **"View Logs"**
3. Or use Railway CLI:
   ```bash
   railway run php artisan migrate --force
   ```

### Step 10: Get Your App URL

1. Click on your **app service**
2. Go to **"Settings"** → **"Networking"**
3. Click **"Generate Domain"** (free Railway domain)
4. Or add your custom domain

## Your Services on Railway

After setup, you'll have:

1. **App Service** - Main Laravel application (from Dockerfile)
2. **Queue Service** - Background job worker (same Dockerfile, different command)
3. **Scheduler Service** - Cron jobs (same Dockerfile, different command)
4. **MySQL Service** - Managed database (Railway's MySQL)

## Environment Variables Location

**Project Level (Recommended):**
- Click **Project** → **"Variables"** tab
- All services share these variables
- Use `${{ServiceName.VARIABLE}}` to reference other services

**Service Level:**
- Click **Service** → **"Variables"** tab
- Override project variables for specific service

## Troubleshooting

### Build Fails - "terser not found"
✅ **Fixed!** I moved `terser` to `dependencies` in `package.json`

### Can't Find Environment Variables
- Look at **Project level** (top menu), not service level
- Click your **Project name** → **"Variables"** tab

### Database Connection Fails
- Verify MySQL service is running
- Check variable names match: `${{MySQL.MYSQLHOST}}`
- Ensure MySQL service name matches in variables

### Services Not Starting
- Check service logs
- Verify start commands are correct
- Ensure all environment variables are set

## Railway CLI (Helpful)

```bash
# Install
npm i -g @railway/cli

# Login
railway login

# Link to project
railway link

# View logs
railway logs

# Run commands
railway run php artisan migrate
railway run php artisan key:generate
```

## Summary

1. ✅ Push code to GitHub
2. ✅ Create Railway project → Deploy from GitHub
3. ✅ Add MySQL database service
4. ✅ Set environment variables at **Project level**
5. ✅ Add Queue service (same Dockerfile, different command)
6. ✅ Add Scheduler service (same Dockerfile, different command)
7. ✅ Generate APP_KEY
8. ✅ Run migrations
9. ✅ Get your app URL

**That's it! Your Docker app is deployed on Railway!** 🚀

