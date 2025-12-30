#!/bin/bash

echo "🔧 Fixing Email Configuration in Docker..."
echo ""

# Step 1: Clear all caches
echo "1️⃣ Clearing configuration cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
echo "✅ Caches cleared"
echo ""

# Step 2: Restart queue worker
echo "2️⃣ Restarting queue worker..."
docker-compose restart queue
echo "✅ Queue worker restarted"
echo ""

# Step 3: Wait a moment for queue to start
echo "3️⃣ Waiting for queue worker to initialize..."
sleep 3
echo ""

# Step 4: Check queue status
echo "4️⃣ Checking queue worker status..."
docker-compose ps queue
echo ""

# Step 5: Clear failed jobs (optional - uncomment if needed)
# echo "5️⃣ Clearing old failed jobs..."
# docker-compose exec app php artisan queue:flush
# echo ""

echo "✅ Email configuration fix complete!"
echo ""
echo "📧 To test email, run:"
echo "   docker-compose exec app php artisan mail:test your-email@gmail.com"
echo ""
echo "📊 To monitor queue logs:"
echo "   docker-compose logs -f queue"
echo ""
echo "🔍 To check failed jobs:"
echo "   docker-compose exec app php artisan queue:failed"

