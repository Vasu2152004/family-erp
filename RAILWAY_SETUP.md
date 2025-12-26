# Railway.app Deployment Guide

## Step-by-Step Setup

### 1. Sign Up & Create Project

1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub
3. Click **"New Project"**
4. Select **"Deploy from GitHub repo"**
5. Choose your repository

### 2. Configure Services

Railway will detect your `docker-compose.production.yml`. You need to set up:

#### Main App Service
- Railway will create services from your docker-compose
- Or manually add a service and select "Dockerfile"

#### Database Service
- Click **"+ New"** → **"Database"** → **"MySQL"**
- Railway will create a MySQL database automatically
- Note the connection details

### 3. Set Environment Variables

**Where to find Environment Variables on Railway:**

1. Click on your **project** (not a service)
2. Go to **"Variables"** tab (top menu)
3. OR click on a specific **service** → **"Variables"** tab

**Required Environment Variables:**

```env
# Application
APP_NAME="Home Flow"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key-here
APP_URL=https://your-app-name.up.railway.app

# Database (from Railway MySQL service)
DB_CONNECTION=mysql
DB_HOST=containers-us-west-xxx.railway.app
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=your-railway-password

# Mail Configuration
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

# Railway Port (Railway sets this automatically)
PORT=80
```

### 4. Get Database Connection Details

1. Click on your **MySQL service**
2. Go to **"Variables"** tab
3. You'll see:
   - `MYSQLHOST` → Use as `DB_HOST`
   - `MYSQLPORT` → Use as `DB_PORT`
   - `MYSQLDATABASE` → Use as `DB_DATABASE`
   - `MYSQLUSER` → Use as `DB_USERNAME`
   - `MYSQLPASSWORD` → Use as `DB_PASSWORD`

### 5. Generate APP_KEY

In Railway:
1. Go to your **app service**
2. Click **"Deployments"** → **"View Logs"**
3. Or use Railway CLI:
   ```bash
   railway run php artisan key:generate
   ```

### 6. Run Migrations

After first deployment:
1. Go to your **app service**
2. Click **"Deployments"** → **"View Logs"**
3. Or use Railway CLI:
   ```bash
   railway run php artisan migrate --force
   ```

### 7. Configure Services

Railway needs these services running:
- **App**: Main Laravel application
- **Queue**: Background job worker
- **Scheduler**: Cron jobs
- **Web**: Nginx (or use Railway's built-in web server)

**Option A: Use Railway's Built-in Web Server**
- Railway can serve PHP directly
- No need for separate Nginx service
- Set `PORT` environment variable

**Option B: Use Docker Compose**
- Railway supports docker-compose
- All services will be deployed

### 8. Railway-Specific Configuration

Create `railway.json` in project root:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "docker/Dockerfile"
  },
  "deploy": {
    "startCommand": "php-fpm",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### 9. Custom Domain (Optional)

1. Go to your **service**
2. Click **"Settings"** → **"Networking"**
3. Click **"Generate Domain"** (free)
4. Or add your custom domain

### 10. Monitor & Logs

- **Logs**: Click service → **"Deployments"** → **"View Logs"**
- **Metrics**: Click service → **"Metrics"** tab
- **Health**: Railway automatically monitors service health

## Troubleshooting

### Build Fails
- Check build logs in Railway
- Ensure all dependencies are in `package.json`
- Verify Dockerfile is correct

### Database Connection Fails
- Check database service is running
- Verify environment variables match Railway's MySQL service
- Ensure `DB_HOST` uses Railway's internal hostname

### Services Not Starting
- Check logs for errors
- Verify all environment variables are set
- Ensure ports are configured correctly

## Railway CLI (Optional)

Install Railway CLI for easier management:

```bash
npm i -g @railway/cli
railway login
railway link
railway up
```

## Cost

- **Free Tier**: $5/month credit
- Usually enough for small apps
- Pay-as-you-go after free credit

## Quick Checklist

- [ ] Project created on Railway
- [ ] GitHub repo connected
- [ ] MySQL database service added
- [ ] All environment variables set
- [ ] APP_KEY generated
- [ ] Migrations run
- [ ] Services deployed and running
- [ ] Domain configured (optional)
- [ ] Health check passing

## Support

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- Railway Status: https://status.railway.app

