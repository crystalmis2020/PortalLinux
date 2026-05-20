# Laravel LAMP deployment from `/mnt`

This project is a Laravel 11 app and can be served with Apache + PHP + MySQL while keeping both the codebase and MySQL database files on the larger `/mnt` disk.

Project path used below:

```text
/mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
```

## 1. Install the LAMP pieces

On Ubuntu:

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl libapache2-mod-php composer npm unzip
```

If your distro ships a slightly different PHP package name, use that version consistently.

## 2. Keep the app on `/mnt`

You can serve the app directly from `/mnt`. For Laravel, Apache must point at the `public/` directory, not the project root:

```text
/mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1/public
```

## 3. Create the Laravel environment file

This repo currently does not have a `.env` file checked in, so create one first:

```bash
cd /mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
cp .env.example .env
```

If `.env.example` is also missing, create `.env` manually with at least:

```dotenv
APP_NAME="Support Portal"
APP_ENV=production
APP_KEY=
APP_DEBUG=true
APP_URL=http://support-portal.local

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=support_portal
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=log
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

## 4. Install PHP and frontend dependencies

```bash
cd /mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
composer install
npm install
npm run build
php artisan key:generate
```

## 5. Prepare writable Laravel folders

```bash
cd /mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

If Apache cannot write to the mounted directory because of `/mnt/hgfs` permission behavior, a quick workaround is:

```bash
chmod -R 777 storage bootstrap/cache
```

That is acceptable for local development, but for long-term production we should tighten that up.

## 6. Create the MySQL database

For this LAMP setup, MySQL should store its database files on `/mnt` instead of the default `/var/lib/mysql` location. The example below uses:

```text
/mnt/mysql
```

Stop MySQL, copy the existing data directory, and update the MySQL datadir:

```bash
sudo systemctl stop mysql
sudo mkdir -p /mnt/mysql
sudo rsync -av /var/lib/mysql/ /mnt/mysql/
sudo chown -R mysql:mysql /mnt/mysql
```

Edit the MySQL server config:

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Set:

```ini
datadir = /mnt/mysql
```

If AppArmor is enabled, allow MySQL to read and write the new `/mnt` database location:

```bash
sudo nano /etc/apparmor.d/usr.sbin.mysqld
```

Add these lines near the other MySQL data-directory rules:

```text
/mnt/mysql/ r,
/mnt/mysql/** rwk,
```

Then reload AppArmor and restart MySQL:

```bash
sudo systemctl reload apparmor
sudo systemctl start mysql
sudo systemctl status mysql
```

After MySQL is running from `/mnt`, create the Laravel database:

```bash
sudo mysql
```

Then run:

```sql
CREATE DATABASE support_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'support_portal_user'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON support_portal.* TO 'support_portal_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env` with the same database name, username, and password.

## 7. Run Laravel migrations

```bash
cd /mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
php artisan migrate
```

## 8. Enable the Apache site

Use the example vhost in `deploy/apache-support-portal.conf.example` and copy it into Apache:

```bash
sudo cp deploy/apache-support-portal.conf.example /etc/apache2/sites-available/support-portal.conf
sudo a2enmod rewrite
sudo a2ensite support-portal.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

If the default site conflicts, disable it:

```bash
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

## 9. Local hostname

Add a hosts entry on the same machine:

```bash
sudo nano /etc/hosts
```

Add:

```text
127.0.0.1 support-portal.local
```

Then open:

```text
http://support-portal.local
```

## 10. Helpful Laravel optimizations

After the app works:

```bash
cd /mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 11. IPMsg notifications on LAMP

The app sends IP Messenger notifications directly from PHP over UDP port `2425`. Users can receive them from Windows or Linux IP Messenger clients as long as:

- Their saved user IP address is correct.
- Their IP Messenger client is running.
- The client machine allows inbound UDP `2425` from the Laravel server.
- The server can send outbound UDP `2425` to the client subnet.

After changing PHP helper code, reload Apache/PHP so OPcache does not keep the old helper loaded:

```bash
sudo systemctl reload apache2
```

If IPMsg still does not arrive, check Laravel logs for the exact sender warning:

```bash
tail -n 100 storage/logs/laravel.log
```

## Common `/mnt` caveats

- Apache must target `public/`, never the repo root.
- Mounted folders like `/mnt/hgfs` sometimes behave differently with file ownership and symlinks.
- If uploads, logs, or cache writes fail, the first places to check are `storage/`, `bootstrap/cache/`, and Apache error logs.
- If Apache refuses to read from `/mnt`, you may need to permit that path in your system security layer as well.

## Apache log locations

```text
/var/log/apache2/error.log
/var/log/apache2/access.log
```

## Quick smoke test

Run these from the project root:

```bash
php artisan about
php artisan migrate:status
```

If both work and Apache points to `public/`, the app is usually ready to load.
