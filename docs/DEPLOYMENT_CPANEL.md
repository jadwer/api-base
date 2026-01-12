# Deployment Guide - cPanel (AlmaLinux)

## Prerequisites

- cPanel & WHM v132+
- PHP 8.2+ (8.3 recommended)
- MySQL 8.0+ or MariaDB 10.6+
- Composer installed
- Git installed (optional, for git-based deploy)

## Server Requirements

### PHP Extensions Required
```
BCMath, Ctype, cURL, DOM, Fileinfo, GD, JSON, Mbstring,
OpenSSL, PCRE, PDO, PDO_MySQL, Tokenizer, XML, Zip
```

Verify in cPanel: **Select PHP Version** > **Extensions**

---

## Step 1: Create Database

1. **cPanel** > **MySQL Databases**
2. Create database: `apibase_dev` (or your preferred name)
3. Create user: `apibase_user`
4. Add user to database with **ALL PRIVILEGES**

Save credentials:
```
DB_DATABASE=apibase_dev
DB_USERNAME=apibase_user
DB_PASSWORD=your_secure_password
```

---

## Step 2: Upload Files

### Option A: Git Clone (Recommended)

```bash
# SSH into server
ssh user@your-server.com

# Navigate to domain directory
cd ~/public_html
# Or subdomain: cd ~/api.yourdomain.com

# Clone repository
git clone https://github.com/your-repo/api-base.git .
```

### Option B: File Upload

1. Create a zip of the project (excluding vendor, node_modules)
2. Upload via **cPanel** > **File Manager**
3. Extract in target directory

---

## Step 3: Configure Document Root

**IMPORTANT**: Laravel's entry point is `/public`, not root.

### Option 1: Subdomain (Recommended for API)

1. **cPanel** > **Subdomains**
2. Create: `api.yourdomain.com`
3. Document Root: `/home/user/api.yourdomain.com/public`

### Option 2: Addon Domain

1. **cPanel** > **Addon Domains**
2. Document Root: `/home/user/public_html/api-base/public`

### Option 3: Main Domain (Not Recommended)

Create `.htaccess` in root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Step 4: Environment Configuration

### Create .env file

```bash
cd ~/api.yourdomain.com  # or your path
cp .env.example .env
```

### Edit .env for Production

```env
APP_NAME="API Base ERP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Timezone
APP_TIMEZONE=America/Mexico_City

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=apibase_dev
DB_USERNAME=apibase_user
DB_PASSWORD=your_secure_password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (configure with your provider)
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Stripe (if using payments)
STRIPE_PUBLISHABLE_KEY=pk_live_xxxx
STRIPE_SECRET_KEY=sk_live_xxxx

# SW PAC (if using CFDI)
SW_PAC_ENABLED=false
SW_PAC_URL=https://services.test.sw.com.mx
SW_PAC_TOKEN=

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## Step 5: Install Dependencies

```bash
cd ~/api.yourdomain.com

# Install PHP dependencies (production)
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Create storage link
php artisan storage:link
```

---

## Step 6: Set Permissions

```bash
# Set ownership (replace 'user' with your cPanel username)
chown -R user:user ~/api.yourdomain.com

# Set directory permissions
find ~/api.yourdomain.com -type d -exec chmod 755 {} \;

# Set file permissions
find ~/api.yourdomain.com -type f -exec chmod 644 {} \;

# Make storage and cache writable
chmod -R 775 ~/api.yourdomain.com/storage
chmod -R 775 ~/api.yourdomain.com/bootstrap/cache
```

---

## Step 7: Run Migrations and Seed

```bash
# Run migrations
php artisan migrate --force

# Seed database (includes roles, permissions, demo data)
php artisan db:seed --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 8: Configure Cron Jobs

**cPanel** > **Cron Jobs**

Add this command (runs every minute):
```
* * * * * cd /home/user/api.yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

---

## Step 9: SSL Certificate

**cPanel** > **SSL/TLS Status**

1. Enable AutoSSL for your domain
2. Or install Let's Encrypt via **cPanel** > **Let's Encrypt**

---

## Step 10: Verify Installation

### Test API Endpoint

```bash
curl https://api.yourdomain.com/api/v1/health
```

Expected response:
```json
{
  "status": "ok",
  "timestamp": "2026-01-12T..."
}
```

### Test Authentication

```bash
curl -X POST https://api.yourdomain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"god@example.com","password":"password"}'
```

---

## Troubleshooting

### 500 Internal Server Error

1. Check `storage/logs/laravel.log`
2. Verify permissions on storage/cache directories
3. Check PHP version compatibility

### 404 Not Found

1. Verify `.htaccess` exists in `/public`
2. Check mod_rewrite is enabled
3. Verify document root points to `/public`

### Database Connection Error

1. Verify credentials in `.env`
2. Test connection: `php artisan db:show`
3. Check database host (use `localhost` not `127.0.0.1`)

### Permission Denied

```bash
chmod -R 775 storage bootstrap/cache
chown -R user:user storage bootstrap/cache
```

---

## Updating the Application

```bash
cd ~/api.yourdomain.com

# Pull latest changes (if using git)
git pull origin master

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

---

## Quick Deploy Script

Save as `deploy.sh` and run after git pull:

```bash
#!/bin/bash
set -e

echo "Starting deployment..."

# Maintenance mode
php artisan down

# Pull changes
git pull origin master

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Restart queue workers (if using)
# php artisan queue:restart

# Exit maintenance mode
php artisan up

echo "Deployment complete!"
```

Make executable: `chmod +x deploy.sh`

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] SSL certificate installed
- [ ] `.env` not accessible via web
- [ ] `storage/` not accessible via web
- [ ] Sensitive files in `.gitignore`
- [ ] Regular backups configured

---

## Default Users (After Seeding)

| Email | Password | Role |
|-------|----------|------|
| god@example.com | password | god |
| admin@example.com | password | admin |
| tech@example.com | password | tech |
| customer@example.com | password | customer |

**IMPORTANT**: Change these passwords immediately in production!

```bash
php artisan tinker
>>> User::where('email', 'god@example.com')->first()->update(['password' => bcrypt('your-new-secure-password')])
```
