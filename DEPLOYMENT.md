# 🚀 Deployment Guide - Barangay ACLC System

## Live Domain
- **URL**: https://barangay.gov.aclc

## Pre-Deployment Checklist

- [ ] Git repository initialized
- [ ] All files committed to GitHub
- [ ] `.env.local` created with live database credentials
- [ ] Live database created and imported
- [ ] reCAPTCHA domain verified in Google Console
- [ ] HTTPS certificate configured
- [ ] PHP 8+ enabled on live server

## Deployment Steps

### 1. Initialize Git Repository (First Time Only)
```bash
cd c:\xampp\htdocs\barangay-final2
git init
git config user.name "Your Name"
git config user.email "your@email.com"
git add .
git commit -m "Initial commit - Barangay ACLC System"
```

### 2. Create GitHub Repository
1. Go to https://github.com/new
2. Create repository: `barangay-aclc`
3. Follow GitHub instructions to push existing code:
```bash
git remote add origin https://github.com/YOUR_USERNAME/barangay-aclc.git
git branch -M main
git push -u origin main
```

### 3. Setup Live Server

#### Option A: cPanel/Plesk Hosting
1. Clone from GitHub into `public_html`:
   ```bash
   cd public_html
   git clone https://github.com/YOUR_USERNAME/barangay-aclc.git
   ```
2. Create `.env.local` with live credentials:
   ```php
   DB_HOST=your-live-db-host
   DB_USER=your-live-db-user
   DB_PASS=your-secure-password
   DB_NAME=barangay_db
   RECAPTCHA_SECRET=6LcXNOksAAAAAI9t798VvVZcxcr8WyoL7ZxTH3Fx
   ```

#### Option B: VPS/Dedicated Server (SSH)
```bash
ssh user@barangay.gov.aclc
cd /var/www/html
git clone https://github.com/YOUR_USERNAME/barangay-aclc.git
cd barangay-aclc
cp .env.example .env.local
# Edit .env.local with live credentials
chmod 755 -R ./
```

### 4. Setup Live Database

```bash
# SSH into server or use phpMyAdmin
mysql -u root -p

CREATE DATABASE barangay_db;
USE barangay_db;
-- Import database.sql (without sample test accounts if desired)
SOURCE database.sql;

-- Update admin password if needed
UPDATE users SET password = PASSWORD_BCRYPT('your-secure-password') WHERE email = 'admin@barangay.gov.ph';
```

### 5. Configure HTTPS

Use **Let's Encrypt** (free SSL):
- cPanel: AutoSSL feature
- Nginx: Certbot
- Apache: Mod_SSL

### 6. Update Configuration

Edit `api/config.php`:
```php
// For production, add environment variable support
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'barangay_db');
define('RECAPTCHA_SECRET', getenv('RECAPTCHA_SECRET') ?: '...');
```

### 7. Update reCAPTCHA

1. Go to: https://www.google.com/recaptcha/admin
2. Add domain: `barangay.gov.aclc`
3. Verify keys match

### 8. Pull Updates (After Changes)

When you make changes locally:
```bash
git add .
git commit -m "Feature: description"
git push origin main
```

Then on live server:
```bash
cd /path/to/barangay-aclc
git pull origin main
```

## Security Checklist

- [ ] `.env.local` is in `.gitignore` (never commit secrets!)
- [ ] Database password is strong (20+ characters, mix of upper/lower/numbers/symbols)
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] PHP error_reporting disabled in production
- [ ] File permissions: 644 (files), 755 (directories)
- [ ] Backup database daily
- [ ] Monitor error logs

## Troubleshooting

**reCAPTCHA fails**
- Verify domain in Google Console
- Check secret key matches config.php
- Clear browser cache

**Database connection error**
- Verify credentials in .env.local
- Check database exists
- Verify user has proper permissions

**Permission denied errors**
```bash
chmod 755 -R /var/www/html/barangay-aclc
chmod 644 /var/www/html/barangay-aclc/*.php
```

## Support

For issues, check:
- `api/` files for backend errors
- Browser Console (F12) for frontend errors
- Server error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
