# Railway Docker Deployment Guide

## Option 1: Deploy with Docker Compose (Recommended)

Railway supports Docker Compose! Here's how:

### Step 1: Prepare Your Repository

1. Make sure `docker-compose.production.yml` is in your repo root
2. Railway will automatically detect it

### Step 2: Create Railway Project

1. Go to [railway.app](https://railway.app)
2. Click **"New Project"**
3. Select **"Deploy from GitHub repo"**
4. Choose your repository
5. Railway will detect `docker-compose.production.yml`

### Step 3: Configure Services

Railway will create services from your docker-compose:

- **app** - Main Laravel application
- **queue** - Queue worker
- **scheduler** - Cron scheduler
- **web** - Nginx (optional, Railway can serve directly)
- **db** - MySQL (or use Railway's managed MySQL)

### Step 4: Use Railway's Managed MySQL (Recommended)

Instead of Docker MySQL, use Railway's managed MySQL:

1. Click **"+ New"** → **"Database"** → **"MySQL"**
2. Railway creates MySQL automatically
3. Get connection details from service variables

### Step 5: Set Environment Variables

**Where to find Environment Variables:**

1. Click on your **Project** (top level)
2. Click **"Variables"** tab
3. OR click on individual **service** → **"Variables"** tab

**Add these variables:**

```env
# Application
APP_NAME="Home Flow"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-key-here
APP_URL=https://your-app.up.railway.app

# Database (from Railway MySQL service)
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

**Note:** Railway uses `${{ServiceName.VARIABLE}}` syntax to reference other services.

### Step 6: Modify docker-compose for Railway

Railway needs some adjustments. Create `docker-compose.railway.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
      target: production
    restart: always
    env_file:
      - .env
    # Railway will set PORT automatically
    environment:
      - PORT=${PORT:-80}

  queue:
    build:
      context: .
      dockerfile: docker/Dockerfile
      target: production
    restart: always
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    env_file:
      - .env
    depends_on:
      - app

  scheduler:
    build:
      context: .
      dockerfile: docker/Dockerfile
      target: production
    restart: always
    command: >
      sh -c "while true; do
        php artisan schedule:run --verbose --no-interaction
        sleep 60
      done"
    env_file:
      - .env
    depends_on:
      - app
```

**Remove `web` and `db` services** - Railway handles these differently.

## Option 2: Deploy Individual Docker Services

### Step 1: Create Services Manually

1. **App Service:**
   - Click **"+ New"** → **"GitHub Repo"**
   - Select your repo
   - Railway detects Dockerfile automatically
   - Set root directory if needed

2. **Queue Service:**
   - Click **"+ New"** → **"Empty Service"**
   - Connect to same repo
   - Set **Start Command**: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`

3. **Scheduler Service:**
   - Click **"+ New"** → **"Empty Service"**
   - Connect to same repo
   - Set **Start Command**: 
     ```bash
     sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
     ```

4. **MySQL Database:**
   - Click **"+ New"** → **"Database"** → **"MySQL"**
   - Railway creates it automatically

### Step 2: Configure Each Service

For each service:
1. Click on the service
2. Go to **"Settings"** tab
3. Set:
   - **Root Directory**: (if needed)
   - **Dockerfile Path**: `docker/Dockerfile`
   - **Docker Build Context**: `.`
   - **Docker Build Target**: `production`

### Step 3: Set Environment Variables

Set variables at **Project level** (shared) or **Service level** (specific):

**Project Variables** (shared by all services):
- Database connection (reference MySQL service)
- Mail configuration
- App configuration

**Service-Specific Variables**:
- Each service can have its own overrides

## Option 3: Use Railway's Native PHP Support

Railway can deploy PHP without Docker:

1. Create new service
2. Select **"PHP"** template
3. Railway auto-detects Laravel
4. Set environment variables
5. Railway handles PHP-FPM automatically

But you'll need separate services for queue and scheduler.

## Recommended Setup for Your App

### Best Approach: Hybrid

1. **Use Railway's Managed MySQL** (not Docker MySQL)
2. **Deploy App as Docker** (from your Dockerfile)
3. **Deploy Queue as separate service** (same Docker image, different command)
4. **Deploy Scheduler as separate service** (same Docker image, different command)
5. **Use Railway's built-in web server** (no Nginx needed)

### Step-by-Step:

1. **Create MySQL Database:**
   - Click **"+ New"** → **"Database"** → **"MySQL"**
   - Note the connection variables

2. **Create App Service:**
   - Click **"+ New"** → **"GitHub Repo"**
   - Select your repo
   - Railway auto-detects Docker
   - Set environment variables

3. **Create Queue Service:**
   - Click **"+ New"** → **"Empty Service"**
   - Connect to same repo
   - Use same Dockerfile
   - Set start command: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`

4. **Create Scheduler Service:**
   - Click **"+ New"** → **"Empty Service"**
   - Connect to same repo
   - Use same Dockerfile
   - Set start command: `sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"`

5. **Set Environment Variables:**
   - At project level (shared by all)
   - Reference MySQL: `${{MySQL.MYSQLHOST}}`

## Railway-Specific Dockerfile Adjustments

Your Dockerfile is good, but Railway needs:

1. **Port Configuration:**
   - Railway sets `PORT` environment variable
   - Your app should listen on `$PORT`

2. **Health Check:**
   - Railway auto-detects health
   - Or add to Dockerfile

## Quick Start Commands

After deployment, run these via Railway CLI or service logs:

```bash
# Generate APP_KEY
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Build Fails
- Check build logs in Railway
- Ensure Dockerfile path is correct
- Verify all dependencies are installed

### Services Not Starting
- Check service logs
- Verify environment variables
- Ensure start commands are correct

### Database Connection Fails
- Verify MySQL service is running
- Check environment variables use Railway's syntax: `${{MySQL.MYSQLHOST}}`
- Ensure database credentials are correct

## Railway CLI (Optional)

```bash
# Install
npm i -g @railway/cli

# Login
railway login

# Link project
railway link

# View logs
railway logs

# Run commands
railway run php artisan migrate
```

## Cost

- **Free Tier**: $5/month credit
- Usually enough for small apps
- Each service uses credits based on usage

---

**Your app is ready for Railway deployment!** 🚀

