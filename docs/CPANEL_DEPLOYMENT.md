# cPanel Deployment Guide for EduBoutique

Deploy EduBoutique on cPanel shared hosting.

## Prerequisites

- cPanel hosting with PHP 8.2+
- MySQL database
- File Manager or FTP access

## Step 1: Upload Files

1. Zip the entire project folder
2. Upload the zip to `public_html` via cPanel File Manager
3. Extract the zip (creates `eduboutique` folder or similar)
4. Create a `.htaccess` file in `public_html` root with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ eduboutique/public/$1 [L]
</IfModule>
```

**Note:** Replace `eduboutique` with your actual folder name.

### Folder Structure
```
public_html/
├── .htaccess              ← Routes to Laravel public folder
├── eduboutique/           ← Your extracted Laravel app
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── public/            ← Actual public files served via .htaccess
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── ...
```

## Step 2: Create MySQL Database

1. In cPanel → **MySQL Databases**
2. Create a new database (e.g., `username_eduboutique`)
3. Create a database user with a strong password
4. Add the user to the database with ALL PRIVILEGES

## Step 3: Configure Environment

1. Copy `.env.example` to `.env` in your Laravel folder
2. Edit `.env` with these values:

```env
APP_NAME="EduBoutique"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_eduboutique
DB_USERNAME=username_dbuser
DB_PASSWORD=your-db-password

# Generate via: https://generate.plus/en/base64 (32 chars then base64)
APP_KEY=base64:YOUR_GENERATED_KEY

# Odoo Integration
ODOO_URL=https://eduboutique.odoo.com
ODOO_DATABASE=eduboutique
ODOO_USERNAME=your-odoo-email
ODOO_PASSWORD="your-odoo-password"

# cPanel Secret (random string, 32+ chars)
CPANEL_SECRET=your-very-long-random-secret-key-here
```

## Step 4: Set Folder Permissions

Via File Manager, right-click and set permissions:
- `storage/` → 775 (recursive)
- `bootstrap/cache/` → 775

## Step 5: Initialize Application

Open these URLs in your browser:

### 1. Check Status
```
https://yourdomain.com/cpanel/status?key=YOUR_CPANEL_SECRET
```

### 2. Create Storage Directories
```
https://yourdomain.com/cpanel/storage-link?key=YOUR_CPANEL_SECRET
```

### 3. Run Migrations
```
https://yourdomain.com/cpanel/migrate?key=YOUR_CPANEL_SECRET
```

### 4. Seed Database
```
https://yourdomain.com/cpanel/seed?key=YOUR_CPANEL_SECRET
```

### 5. Create Admin User
```
https://yourdomain.com/cpanel/create-admin?key=YOUR_CPANEL_SECRET&email=admin@yourdomain.com&password=SecurePass123&name=Admin
```

### 6. Optimize for Production
```
https://yourdomain.com/cpanel/optimize?key=YOUR_CPANEL_SECRET
```

## Step 6: Set Up Cron Jobs

In cPanel → **Cron Jobs**, add:

### Process Queue (Every Minute)
```
* * * * * wget -q -O /dev/null "https://yourdomain.com/cron/queue?key=YOUR_CPANEL_SECRET"
```

### Odoo Stock Sync (Every Hour)
```
0 * * * * wget -q -O /dev/null "https://yourdomain.com/cron/odoo-sync?key=YOUR_CPANEL_SECRET"
```

### Full Odoo Sync (Daily at 2 AM)
```
0 2 * * * wget -q -O /dev/null "https://yourdomain.com/cron/odoo-full-sync?key=YOUR_CPANEL_SECRET"
```

## Management URLs

All require `?key=YOUR_CPANEL_SECRET`

| URL | Description |
|-----|-------------|
| `/cpanel/status` | Check app status |
| `/cpanel/migrate` | Run migrations |
| `/cpanel/migrate-fresh` | Fresh migrations (DROPS ALL DATA) |
| `/cpanel/seed` | Seed database |
| `/cpanel/seed?class=BookSeeder` | Run specific seeder |
| `/cpanel/clear-cache` | Clear all caches |
| `/cpanel/optimize` | Optimize for production |
| `/cpanel/storage-link` | Create storage directories |
| `/cpanel/odoo-sync?type=status` | Odoo sync status |
| `/cpanel/odoo-sync?type=all` | Full Odoo sync |
| `/cpanel/queue-work` | Process queue job |
| `/cpanel/create-admin` | Create admin user |

## Troubleshooting

### 500 Error
- Check PHP version is 8.2+
- Check `.env` exists and is configured
- Check folder permissions (storage, bootstrap/cache)
- View cPanel → Error Log

### Images Not Loading
- Run `/cpanel/storage-link?key=...` to create directories
- Check `storage/app/public` exists

### Admin Panel Not Loading
- Run `/cpanel/migrate?key=...`
- Run `/cpanel/create-admin?key=...`

## Updating

1. Upload new files (backup first!)
2. `/cpanel/migrate?key=...`
3. `/cpanel/clear-cache?key=...`
4. `/cpanel/optimize?key=...`

## Security

- Keep `CPANEL_SECRET` private
- Set `APP_DEBUG=false` in production
- Enable SSL/HTTPS
- Regular backups via cPanel
